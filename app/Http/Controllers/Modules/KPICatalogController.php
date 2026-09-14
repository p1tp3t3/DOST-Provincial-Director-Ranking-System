<?php

namespace App\Http\Controllers\Modules;

use App\Events\KpiCatalogUpdated;
use App\Http\Controllers\Controller;
use App\Models\KPI;
use App\Models\KPICategory;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * General KPI catalog management — Super Admin + Sub Admin only. This is a
 * single shared matrix used to evaluate every province (see RankingService),
 * so it's managed here as one thing, not per province. Setting a specific
 * province's target/accomplished for a KPI still happens on the per-province
 * screen at /kpi-data; this page only owns name, category, and weight.
 */
class KPICatalogController extends Controller
{
    public function index()
    {
        $categories = KPICategory::with(['kpis' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')->get()
            ->map(fn($cat) => [
                'id'     => $cat->id,
                'code'   => $cat->code,
                'name'   => $cat->name,
                'weight' => (float) $cat->weight,
                'kpis'   => $cat->kpis->map(fn($k) => [
                    'id'              => $k->id,
                    'category_id'     => $k->category_id,
                    'code'            => $k->code,
                    'name'            => $k->name,
                    'weight'          => (float) $k->weight,
                    'is_scored'       => (bool) $k->is_scored,
                    'inverse_scoring' => (bool) $k->inverse_scoring,
                    'derivation_type' => $k->derivation_type,
                ])->values()->toArray(),
            ])->values()->toArray();

        return inertia('Admin/KpiCatalog/Main', [
            'kpi_categories'   => $categories,
            'category_options' => KPICategory::orderBy('sort_order')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:kpi_categories,id'],
            'name'        => ['required', 'string', 'max:500'],
            'weight'      => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $nextSort = (int) (KPI::where('category_id', $data['category_id'])->max('sort_order') ?? 0) + 1;

        $kpi = KPI::create([
            'category_id'     => $data['category_id'],
            'code'            => $this->generateKpiCode($data['name']),
            'name'            => $data['name'],
            'weight'          => $data['weight'] / 100,
            'is_scored'       => $data['weight'] > 0,
            'inverse_scoring' => false,
            'derivation_type' => null,
            'sort_order'      => $nextSort,
        ]);

        broadcast(new KpiCatalogUpdated('created', $kpi->id, $kpi->name, $kpi->category_id));

        return back()->with('success', "\"{$kpi->name}\" added to the KPI matrix.");
    }

    // Commits every edited category/weight change from the catalog table in one
    // go, after the admin has reviewed them in the preview modal. Applied in a
    // single transaction; broadcasts once so open screens refresh together.
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'changes'                => ['required', 'array', 'min:1'],
            'changes.*.id'           => ['required', 'integer', 'exists:kpis,id'],
            'changes.*.name'         => ['required', 'string', 'max:500'],
            'changes.*.category_id'  => ['required', 'integer', 'exists:kpi_categories,id'],
            'changes.*.weight'       => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $editedIds = collect($data['changes'])->pluck('id');
        $newNames  = collect($data['changes'])->pluck('name')->map(fn ($n) => strtolower(trim($n)));

        if ($newNames->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['changes' => 'Two of the edited KPIs would end up with the same name.']);
        }

        $conflict = KPI::whereNotIn('id', $editedIds)
            ->whereIn(DB::raw('LOWER(name)'), $newNames->all())
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages(['changes' => 'One of the new names already belongs to another KPI.']);
        }

        $updatedIds = DB::transaction(function () use ($data) {
            $ids = [];
            foreach ($data['changes'] as $change) {
                $kpi = KPI::find($change['id']);
                if (!$kpi) continue;

                $kpi->update([
                    'name'        => trim($change['name']),
                    'category_id' => $change['category_id'],
                    'weight'      => $change['weight'] / 100,
                    // Dynamic: a KPI counts toward the ranking only while it carries
                    // weight. Zeroing the weight is how an admin turns scoring off.
                    'is_scored'   => $change['weight'] > 0,
                ]);
                $ids[] = $kpi->id;
            }
            return $ids;
        });

        broadcast(new KpiCatalogUpdated('bulk_updated', $updatedIds[0] ?? 0, count($updatedIds) . ' KPIs updated', null));

        return back()->with('success', count($updatedIds) . ' KPI(s) updated.');
    }

    public function destroy(int $kpiId)
    {
        $kpi = KPI::findOrFail($kpiId);
        $name = $kpi->name;
        $categoryId = $kpi->category_id;
        $kpi->delete(); // soft delete — historical provincial_director_kpis rows are untouched

        broadcast(new KpiCatalogUpdated('deleted', $kpiId, $name, $categoryId));

        return back()->with('success', "\"{$name}\" removed from the KPI matrix. Historical data is preserved.");
    }

    // ── Bulk add via CSV (name, category, weight) ─────────────────────────
    // Verify → review → commit, mirroring the bulk-employee CSV pattern minus
    // the job-batch queue — a KPI row insert is trivial, so this runs inline.

    public function verifyCsv(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $rows = $this->parseCsv($request->file('csv_file'));

        if (empty($rows)) {
            return response()->json(['message' => 'The CSV file is empty or could not be parsed.'], 422);
        }

        $categories = KPICategory::all(['id', 'code', 'name']);
        $byCode = $categories->keyBy(fn($c) => strtolower($c->code));
        $byName = $categories->keyBy(fn($c) => strtolower($c->name));

        $existingNames = KPI::pluck('name')->map(fn($n) => strtolower(trim($n)))->flip();
        $seenNames = [];
        $results   = [];

        foreach ($rows as $i => $row) {
            $name      = trim($row['name']     ?? '');
            $catRaw    = trim($row['category'] ?? '');
            $weightRaw = trim($row['weight']   ?? '');

            $errors = [];
            $nameKey = strtolower($name);

            if ($name === '') {
                $errors['name'] = 'Name is required.';
            } elseif (isset($existingNames[$nameKey])) {
                $errors['name'] = 'A KPI with this name already exists.';
            } elseif (isset($seenNames[$nameKey])) {
                $errors['name'] = 'Duplicate name in this CSV.';
            } else {
                $seenNames[$nameKey] = true;
            }

            $category = $byCode[strtolower($catRaw)] ?? $byName[strtolower($catRaw)] ?? null;
            if ($catRaw === '') {
                $errors['category'] = 'Category is required.';
            } elseif (!$category) {
                $errors['category'] = 'Unknown category — use Core, Strategic, or Support.';
            }

            $weight = is_numeric($weightRaw) ? (float) $weightRaw : null;
            if ($weightRaw === '') {
                $errors['weight'] = 'Weight is required.';
            } elseif ($weight === null || $weight < 0 || $weight > 100) {
                $errors['weight'] = 'Weight must be a number between 0 and 100.';
            }

            $results[] = [
                'row_index' => $i,
                'status'    => empty($errors) ? 'valid' : 'invalid',
                'errors'    => $errors,
                'include'   => empty($errors),
                'data'      => [
                    'name'        => $name,
                    'category_id' => $category?->id,
                    'category'    => $category?->name ?? $catRaw,
                    'weight'      => $weight,
                ],
            ];
        }

        return response()->json([
            'status'           => 'done',
            'results'          => $results,
            'total'            => count($results),
            'category_options' => $categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values(),
        ]);
    }

    public function commitCsv(Request $request)
    {
        $data = $request->validate([
            'rows'               => ['required', 'array', 'min:1'],
            'rows.*.name'        => ['required', 'string', 'max:500'],
            'rows.*.category_id' => ['required', 'integer', 'exists:kpi_categories,id'],
            'rows.*.weight'      => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $createdIds = DB::transaction(function () use ($data) {
            $ids = [];
            foreach ($data['rows'] as $row) {
                $nextSort = (int) (KPI::where('category_id', $row['category_id'])->max('sort_order') ?? 0) + 1;

                $kpi = KPI::create([
                    'category_id'     => $row['category_id'],
                    'code'            => $this->generateKpiCode($row['name']),
                    'name'            => $row['name'],
                    'weight'          => $row['weight'] / 100,
                    'is_scored'       => $row['weight'] > 0,
                    'inverse_scoring' => false,
                    'derivation_type' => null,
                    'sort_order'      => $nextSort,
                ]);

                $ids[] = $kpi->id;
            }
            return $ids;
        });

        broadcast(new KpiCatalogUpdated('bulk_created', $createdIds[0] ?? 0, count($createdIds) . ' KPIs imported', null));

        return response()->json(['created' => count($createdIds)]);
    }

    private function parseCsv(UploadedFile $file): array
    {
        $rows    = [];
        $headers = null;
        $handle  = fopen($file->getRealPath(), 'r');

        while (($line = fgetcsv($handle)) !== false) {
            if (!$headers) {
                $headers = array_map(fn($h) => strtolower(trim(str_replace(' ', '_', $h))), $line);
                continue;
            }
            if (count($line) !== count($headers)) continue;
            $row = array_combine($headers, $line);
            if (!empty(array_filter($row))) {
                $rows[] = $row;
            }
        }

        fclose($handle);
        return $rows;
    }

    // Unique, URL/DB-safe code derived from the name (e.g. "Trainings Conducted"
    // → "custom_trainings_conducted"), disambiguated with a numeric suffix on clash.
    private function generateKpiCode(string $name): string
    {
        $base = 'custom_' . Str::slug($name, '_');
        $code = $base;
        $i = 1;
        while (KPI::withTrashed()->where('code', $code)->exists()) {
            $code = $base . '_' . (++$i);
        }
        return $code;
    }
}
