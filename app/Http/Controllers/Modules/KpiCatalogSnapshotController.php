<?php

namespace App\Http\Controllers\Modules;

use App\Events\KpiCatalogUpdated;
use App\Http\Controllers\Controller;
use App\Models\KPI;
use App\Models\KPICategory;
use App\Models\KpiCatalogSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

// Restorable versions of the KPI catalog. A snapshot stores the catalog
// *definition* only — categories, KPI names, weights and which KPIs exist.
// Entered targets/accomplishments in provincial_director_kpis are never
// touched, so restoring an old matrix never destroys collected data.
class KpiCatalogSnapshotController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'note'  => ['nullable', 'string', 'max:1000'],
        ]);

        $snapshot = KpiCatalogSnapshot::create([
            'label'       => trim($data['label']),
            'note'        => $data['note'] ?? null,
            'payload'     => $this->captureCurrentCatalog(),
            'is_baseline' => false,
            'created_by'  => Auth::id(),
        ]);

        return back()->with('success', 'Saved the current KPI catalog as "' . $snapshot->label . '".');
    }

    public function restore(KpiCatalogSnapshot $snapshot)
    {
        $payload = $snapshot->payload;

        if (!is_array($payload) || !isset($payload['kpis'], $payload['categories'])) {
            return back()->with('error', 'That save file is unreadable and cannot be restored.');
        }

        $summary = DB::transaction(function () use ($payload) {
            // Categories first — KPIs reference them by code.
            $categoryIdByCode = [];
            foreach ($payload['categories'] as $cat) {
                $category = KPICategory::firstOrNew(['code' => $cat['code']]);
                $category->fill([
                    'name'       => $cat['name'],
                    'sort_order' => $cat['sort_order'],
                    // Recalculated from the KPI weights below; seeded here so a
                    // brand new category is never written with a null weight.
                    'weight'     => $cat['weight'],
                ])->save();
                $categoryIdByCode[$cat['code']] = $category->id;
            }

            $restored        = 0;
            $recreated       = 0;
            $codesInSnapshot = [];

            foreach ($payload['kpis'] as $row) {
                $codesInSnapshot[] = $row['code'];
                $categoryId = $categoryIdByCode[$row['category_code']] ?? null;
                if (!$categoryId) {
                    continue;
                }

                // withTrashed: a KPI deleted since the snapshot is un-deleted rather
                // than recreated, which keeps its id and its historical score rows.
                $kpi = KPI::withTrashed()->where('code', $row['code'])->first();

                $attributes = [
                    'category_id'     => $categoryId,
                    'name'            => $row['name'],
                    'weight'          => $row['weight'],
                    'is_scored'       => $row['is_scored'],
                    'inverse_scoring' => $row['inverse_scoring'],
                    'derivation_type' => $row['derivation_type'],
                    'sort_order'      => $row['sort_order'],
                ];

                if ($kpi) {
                    $kpi->fill($attributes);
                    if ($kpi->trashed()) {
                        $kpi->deleted_at = null;
                        $recreated++;
                    }
                    $kpi->save();
                } else {
                    KPI::create($attributes + ['code' => $row['code']]);
                    $recreated++;
                }

                $restored++;
            }

            // Anything added after the snapshot is soft-deleted, matching what the
            // catalog's own delete button does — historical data stays intact.
            $removed = KPI::whereNotIn('code', $codesInSnapshot)->get();
            foreach ($removed as $kpi) {
                $kpi->delete();
            }

            // Category weight is always the sum of its KPIs' weights.
            foreach (KPICategory::pluck('id') as $categoryId) {
                KPICategory::whereKey($categoryId)
                    ->update(['weight' => KPI::where('category_id', $categoryId)->sum('weight')]);
            }

            return [
                'restored'  => $restored,
                'removed'   => $removed->count(),
                'recreated' => $recreated,
            ];
        });

        $this->safeBroadcast(new KpiCatalogUpdated(
            'bulk_updated',
            0,
            'Catalog restored to "' . $snapshot->label . '"',
            null
        ));

        $message = 'Restored "' . $snapshot->label . '" — ' . $summary['restored'] . ' KPI(s) in the matrix';
        if ($summary['removed']) {
            $message .= ', ' . $summary['removed'] . ' newer KPI(s) removed';
        }
        if ($summary['recreated']) {
            $message .= ', ' . $summary['recreated'] . ' brought back';
        }

        return back()->with('success', $message . '. Entered scores were not affected.');
    }

    public function destroy(KpiCatalogSnapshot $snapshot)
    {
        if ($snapshot->is_baseline) {
            return back()->with('error', 'The original catalog cannot be deleted.');
        }

        $label = $snapshot->label;
        $snapshot->delete();

        return back()->with('success', 'Deleted save file "' . $label . '".');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    // Current catalog in the same shape the baseline migration writes, so both
    // kinds of save file restore through exactly the same code path.
    private function captureCurrentCatalog(): array
    {
        $categories = KPICategory::orderBy('sort_order')->get();
        $codeById   = $categories->pluck('code', 'id');

        return [
            'version'    => 1,
            'categories' => $categories->map(fn ($c) => [
                'code'       => $c->code,
                'name'       => $c->name,
                'weight'     => (float) $c->weight,
                'sort_order' => (int) $c->sort_order,
            ])->values()->all(),
            'kpis' => KPI::orderBy('sort_order')->get()
                ->map(fn ($k) => [
                    'code'            => $k->code,
                    'category_code'   => $codeById[$k->category_id] ?? null,
                    'name'            => $k->name,
                    'weight'          => (float) $k->weight,
                    'is_scored'       => (bool) $k->is_scored,
                    'inverse_scoring' => (bool) $k->inverse_scoring,
                    'derivation_type' => $k->derivation_type,
                    'sort_order'      => (int) $k->sort_order,
                ])
                ->filter(fn ($k) => $k['category_code'] !== null)
                ->values()->all(),
        ];
    }

    private function safeBroadcast(KpiCatalogUpdated $event): void
    {
        try {
            broadcast($event);
        } catch (Throwable $e) {
            Log::warning('KPI catalog snapshot broadcast failed (the change was still saved): ' . $e->getMessage());
        }
    }
}
