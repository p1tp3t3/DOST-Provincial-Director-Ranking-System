<?php

namespace App\Http\Controllers\Modules;

use App\Events\KpiCatalogUpdated;
use App\Http\Controllers\Controller;
use App\Models\KPI;
use App\Models\KPICategory;
use App\Models\Province;
use App\Models\ProvincialDirectorKPI;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

// Super Admin + Sub Admin editor for the PSTD Ranking Matrix — both the KPI
// catalog itself (add / edit weight & category / soft-delete) and each
// province's accomplishment data. Lives at /kpi-data and is intentionally
// separate from the public-facing Province Directories page (read-only).
// Provincial-level roles have no edit access here at all; central admins are
// the only ones who can manage the matrix.
class KPIDataController extends Controller
{
    // KPI codes whose accomplishment is derived from a dedicated record module.
    // For these, the matrix form must NOT write the accomplishment field —
    // the module (e.g. LinkageController) is the sole source of truth.
    private const MODULAR_KPI_CODES = [
        'func_linkages_established' => '/linkages',
        'supp_facebook_posts'       => '/facebook-posts',
    ];

    public function index()
    {
        $directorNameSql = "CONCAT_WS(' ',
            NULLIF(TRIM(IFNULL(pr.first_name,'')), ''),
            NULLIF(TRIM(IFNULL(pr.middle_name,'')), ''),
            NULLIF(TRIM(IFNULL(pr.last_name,'')), '')
        )";

        $directors = DB::table('user_province as up')
            ->join('users as u', 'u.id', '=', 'up.user_id')
            ->where('u.role', 'provincial_director')
            ->select('up.province_id', 'up.user_id as director_id');

        $provinces = DB::table('provinces as p')
            ->leftJoinSub($directors, 'd', 'd.province_id', '=', 'p.id')
            ->leftJoin('users as u', 'u.id', '=', 'd.director_id')
            ->leftJoin('profiles as pr', 'pr.user_id', '=', 'u.id')
            ->select(
                'p.id', 'p.name', 'p.category',
                'u.id as director_id',
                DB::raw("$directorNameSql as director_name")
            )
            ->orderBy('p.name')->get();

        $yearsByDirector = DB::table('provincial_director_kpis')
            ->select('provincial_director_id', DB::raw('GROUP_CONCAT(DISTINCT year ORDER BY year DESC) as years'))
            ->groupBy('provincial_director_id')->pluck('years', 'provincial_director_id');

        $list = $provinces->map(function ($p) use ($yearsByDirector) {
            $years = $p->director_id && isset($yearsByDirector[$p->director_id])
                ? array_values(array_filter(explode(',', $yearsByDirector[$p->director_id])))
                : [];

            return [
                'id'          => Crypt::encrypt($p->id),
                'name'        => $p->name,
                'category'    => $p->category,
                'director_id' => $p->director_id,
                'director'    => $p->director_id ? trim($p->director_name) ?: '—' : null,
                'years'       => $years,
                'years_count' => count($years),
            ];
        })->values();

        $svc = new RankingService();

        return inertia('Admin/KpiData/Main', [
            'provinces'        => $list,
            'available_years'  => $svc->availableYears(),
        ]);
    }

    public function edit(string $id, ?int $year = null)
    {
        $provinceId = Crypt::decrypt($id);
        $province   = Province::findOrFail($provinceId);

        $director = User::whereProvince($provinceId)
                        ->where('role', 'provincial_director')
                        ->with('profile')
                        ->first();

        if (!$director) {
            return back()->withErrors(['no_director' => 'Province has no provincial director assigned.']);
        }

        $svc            = new RankingService();
        $availableYears = $svc->availableYears();
        $year           = $year ?: ($availableYears[0] ?? now()->year);

        $directorYears = DB::table('provincial_director_kpis')
            ->where('provincial_director_id', $director->id)
            ->distinct()->orderByDesc('year')->pluck('year')->values()->toArray();

        // Existing values keyed by kpi_id so the form can pre-populate
        $values = ProvincialDirectorKPI::where('provincial_director_id', $director->id)
            ->where('year', $year)
            ->get()
            ->keyBy('kpi_id')
            ->map(fn($r) => ['target' => $r->target, 'accomplished' => $r->accomplished])
            ->toArray();

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
                    'target'          => $values[$k->id]['target']       ?? '',
                    'accomplished'    => $values[$k->id]['accomplished'] ?? '',
                ])->values()->toArray(),
            ])->values()->toArray();

        $categoryOptions = KPICategory::orderBy('sort_order')->get(['id', 'code', 'name'])->toArray();

        $profile = $director->profile;
        $directorName = $profile
            ? trim(($profile->first_name ?? '') . ' ' . ($profile->middle_name ?? '') . ' ' . ($profile->last_name ?? ''))
            : '—';

        return inertia('Admin/KpiData/Edit', [
            'province'           => [
                'id'       => Crypt::encrypt($province->id),
                'name'     => $province->name,
                'category' => $province->category,
            ],
            'category_options'   => $categoryOptions,
            'director'           => [
                'id'   => $director->id,
                'name' => $directorName ?: '—',
            ],
            'year'               => (int) $year,
            'available_years'    => $availableYears,
            'director_years'     => $directorYears,
            'kpi_categories'     => $categories,
            'modular_kpi_codes'  => self::MODULAR_KPI_CODES,
        ]);
    }

    public function update(Request $request, int $directorId, int $year)
    {
        $request->validate([
            'values'                 => ['required', 'array'],
            'values.*.kpi_id'        => ['required', 'integer', 'exists:kpis,id'],
            'values.*.target'        => ['nullable', 'string', 'max:255'],
            'values.*.accomplished'  => ['nullable', 'string', 'max:255'],
            'values.*.category_id'   => ['nullable', 'integer', 'exists:kpi_categories,id'],
            'values.*.weight'        => ['nullable', 'numeric', 'min:0', 'max:1'],
        ]);

        $director = User::where('id', $directorId)
            ->where('role', 'provincial_director')->firstOrFail();

        $modularKpiIds = KPI::whereIn('code', array_keys(self::MODULAR_KPI_CODES))->pluck('id')->all();
        $catalogChanges = [];

        DB::transaction(function () use ($request, $director, $year, $modularKpiIds, &$catalogChanges) {
            foreach ($request->input('values') as $row) {
                $target       = $this->normalizeInput($row['target']       ?? null);
                $accomplished = $this->normalizeInput($row['accomplished'] ?? null);
                $isModular    = in_array((int) $row['kpi_id'], $modularKpiIds, true);

                // Catalog-level fields (shared across every province) — only touch
                // the row if the client actually sent a change for it.
                if (array_key_exists('category_id', $row) || array_key_exists('weight', $row)) {
                    $kpi = KPI::find($row['kpi_id']);
                    if ($kpi) {
                        $catalogUpdate = [];
                        if (array_key_exists('category_id', $row) && $row['category_id'] !== null
                            && (int) $row['category_id'] !== $kpi->category_id) {
                            $catalogUpdate['category_id'] = (int) $row['category_id'];
                        }
                        if (array_key_exists('weight', $row) && $row['weight'] !== null
                            && (float) $row['weight'] !== (float) $kpi->weight) {
                            $catalogUpdate['weight'] = (float) $row['weight'];
                        }
                        if ($catalogUpdate) {
                            $kpi->update($catalogUpdate);
                            $catalogChanges[] = $kpi->id;
                        }
                    }
                }

                // For modular KPIs, accomplishment is owned by the module — never
                // touch it from this form. Only target is editable here.
                if ($isModular) {
                    if ($target === null) {
                        // Leave row alone if it exists (module may have set accomplished);
                        // only clear the target.
                        ProvincialDirectorKPI::where('provincial_director_id', $director->id)
                            ->where('kpi_id', $row['kpi_id'])
                            ->where('year', $year)
                            ->update(['target' => null]);
                        continue;
                    }
                    ProvincialDirectorKPI::updateOrCreate(
                        [
                            'provincial_director_id' => $director->id,
                            'kpi_id'                 => $row['kpi_id'],
                            'year'                   => $year,
                        ],
                        ['target' => $target]
                    );
                    continue;
                }

                // Empty target AND accomplished → delete the row to keep the table tidy
                if ($target === null && $accomplished === null) {
                    ProvincialDirectorKPI::where('provincial_director_id', $director->id)
                        ->where('kpi_id', $row['kpi_id'])
                        ->where('year', $year)->delete();
                    continue;
                }

                ProvincialDirectorKPI::updateOrCreate(
                    [
                        'provincial_director_id' => $director->id,
                        'kpi_id'                 => $row['kpi_id'],
                        'year'                   => $year,
                    ],
                    [
                        'target'       => $target,
                        'accomplished' => $accomplished,
                    ]
                );
            }
        });

        // Weight/category changes affect every province's live score — let open
        // KPI Data screens know to refresh rather than show a stale matrix.
        foreach (array_unique($catalogChanges) as $kpiId) {
            $kpi = KPI::find($kpiId);
            if ($kpi) {
                broadcast(new KpiCatalogUpdated('updated', $kpi->id, $kpi->name, $kpi->category_id));
            }
        }

        return back()->with('success', "Saved KPI data for {$year}.");
    }

    // ── KPI Catalog: add / soft-delete (Super Admin + Sub Admin only) ─────────

    public function store_kpi(Request $request)
    {
        $data = $request->validate([
            'category_id'  => ['required', 'integer', 'exists:kpi_categories,id'],
            'name'         => ['required', 'string', 'max:500'],
            'weight'       => ['required', 'numeric', 'min:0', 'max:1'],
            'director_id'  => ['required', 'integer', 'exists:users,id'],
            'year'         => ['required', 'integer', 'min:2000', 'max:2100'],
            'target'       => ['nullable', 'string', 'max:255'],
        ]);

        $director = User::where('id', $data['director_id'])
            ->where('role', 'provincial_director')->firstOrFail();

        $nextSort = (int) (KPI::where('category_id', $data['category_id'])->max('sort_order') ?? 0) + 1;

        $kpi = DB::transaction(function () use ($data, $director, $nextSort) {
            $kpi = KPI::create([
                'category_id'     => $data['category_id'],
                'code'            => $this->generateKpiCode($data['name']),
                'name'            => $data['name'],
                'weight'          => $data['weight'],
                'is_scored'       => true,
                'inverse_scoring' => false,
                'derivation_type' => null,
                'sort_order'      => $nextSort,
            ]);

            $target = $this->normalizeInput($data['target'] ?? null);
            if ($target !== null) {
                ProvincialDirectorKPI::updateOrCreate(
                    ['provincial_director_id' => $director->id, 'kpi_id' => $kpi->id, 'year' => $data['year']],
                    ['target' => $target]
                );
            }

            return $kpi;
        });

        broadcast(new KpiCatalogUpdated('created', $kpi->id, $kpi->name, $kpi->category_id));

        return back()->with('success', "\"{$kpi->name}\" added to the KPI matrix.");
    }

    public function destroy_kpi(int $kpiId)
    {
        $kpi = KPI::findOrFail($kpiId);
        $name = $kpi->name;
        $categoryId = $kpi->category_id;
        $kpi->delete(); // soft delete — historical provincial_director_kpis rows are untouched

        broadcast(new KpiCatalogUpdated('deleted', $kpiId, $name, $categoryId));

        return back()->with('success', "\"{$name}\" removed from the KPI matrix. Historical data is preserved.");
    }

    // ── KPI Catalog: bulk add via CSV (name, category, weight, target) ───────
    // Mirrors the bulk-employee CSV pattern (verify → review → commit), minus
    // the job-batch queue — a KPI row insert is trivial, so this runs inline.

    public function verify_kpi_csv(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $rows = $this->parse_kpi_csv($request->file('csv_file'));

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
            $name    = trim($row['name']     ?? '');
            $catRaw  = trim($row['category'] ?? '');
            $weightRaw = trim($row['weight'] ?? '');
            $target  = trim($row['target']   ?? '');

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
                    'target'      => $target,
                ],
            ];
        }

        return response()->json([
            'status'            => 'done',
            'results'           => $results,
            'total'             => count($results),
            'category_options'  => $categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values(),
        ]);
    }

    public function commit_kpi_csv(Request $request)
    {
        $data = $request->validate([
            'rows'                => ['required', 'array', 'min:1'],
            'rows.*.name'         => ['required', 'string', 'max:500'],
            'rows.*.category_id'  => ['required', 'integer', 'exists:kpi_categories,id'],
            'rows.*.weight'       => ['required', 'numeric', 'min:0', 'max:100'],
            'rows.*.target'       => ['nullable', 'string', 'max:255'],
            'director_id'         => ['required', 'integer', 'exists:users,id'],
            'year'                => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $director = User::where('id', $data['director_id'])
            ->where('role', 'provincial_director')->firstOrFail();

        $createdIds = DB::transaction(function () use ($data, $director) {
            $ids = [];
            foreach ($data['rows'] as $row) {
                $nextSort = (int) (KPI::where('category_id', $row['category_id'])->max('sort_order') ?? 0) + 1;

                $kpi = KPI::create([
                    'category_id'     => $row['category_id'],
                    'code'            => $this->generateKpiCode($row['name']),
                    'name'            => $row['name'],
                    'weight'          => $row['weight'] / 100,
                    'is_scored'       => true,
                    'inverse_scoring' => false,
                    'derivation_type' => null,
                    'sort_order'      => $nextSort,
                ]);

                $target = $this->normalizeInput($row['target'] ?? null);
                if ($target !== null) {
                    ProvincialDirectorKPI::updateOrCreate(
                        ['provincial_director_id' => $director->id, 'kpi_id' => $kpi->id, 'year' => $data['year']],
                        ['target' => $target]
                    );
                }

                $ids[] = $kpi->id;
            }
            return $ids;
        });

        broadcast(new KpiCatalogUpdated('bulk_created', $createdIds[0] ?? 0, count($createdIds) . ' KPIs imported', null));

        return response()->json(['created' => count($createdIds)]);
    }

    private function parse_kpi_csv(UploadedFile $file): array
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
        $base = 'custom_' . \Illuminate\Support\Str::slug($name, '_');
        $code = $base;
        $i = 1;
        while (KPI::withTrashed()->where('code', $code)->exists()) {
            $code = $base . '_' . (++$i);
        }
        return $code;
    }

    private function normalizeInput(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim($v);
        return $v === '' ? null : $v;
    }
}
