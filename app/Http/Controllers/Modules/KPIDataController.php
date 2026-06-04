<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\KPI;
use App\Models\KPICategory;
use App\Models\Province;
use App\Models\ProvincialDirectorKPI;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

// Super-admin-only editor for the PSTD Ranking Matrix accomplishment data.
// Lives at /kpi-data and is intentionally separate from the public-facing
// Province Directories page (which stays read-only).
class KPIDataController extends Controller
{
    public function index()
    {
        $directorNameSql = "CONCAT_WS(' ',
            NULLIF(TRIM(IFNULL(pr.first_name,'')), ''),
            NULLIF(TRIM(IFNULL(pr.middle_name,'')), ''),
            NULLIF(TRIM(IFNULL(pr.last_name,'')), '')
        )";

        $provinces = DB::table('provinces as p')
            ->leftJoin('users as u', function ($j) {
                $j->on('u.province_id', '=', 'p.id')->where('u.role', '=', 'provincial_director');
            })
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

        $director = User::where('province_id', $provinceId)
            ->where('role', 'provincial_director')
            ->with('profile')->first();

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

        $profile = $director->profile;
        $directorName = $profile
            ? trim(($profile->first_name ?? '') . ' ' . ($profile->middle_name ?? '') . ' ' . ($profile->last_name ?? ''))
            : '—';

        return inertia('Admin/KpiData/Edit', [
            'province'        => [
                'id'       => Crypt::encrypt($province->id),
                'name'     => $province->name,
                'category' => $province->category,
            ],
            'director'        => [
                'id'   => $director->id,
                'name' => $directorName ?: '—',
            ],
            'year'            => (int) $year,
            'available_years' => $availableYears,
            'director_years'  => $directorYears,
            'kpi_categories'  => $categories,
        ]);
    }

    public function update(Request $request, int $directorId, int $year)
    {
        $request->validate([
            'values'                 => ['required', 'array'],
            'values.*.kpi_id'        => ['required', 'integer', 'exists:kpis,id'],
            'values.*.target'        => ['nullable', 'string', 'max:255'],
            'values.*.accomplished'  => ['nullable', 'string', 'max:255'],
        ]);

        $director = User::where('id', $directorId)
            ->where('role', 'provincial_director')->firstOrFail();

        DB::transaction(function () use ($request, $director, $year) {
            foreach ($request->input('values') as $row) {
                $target       = $this->normalizeInput($row['target']       ?? null);
                $accomplished = $this->normalizeInput($row['accomplished'] ?? null);

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

        return back()->with('success', "Saved KPI data for {$year}.");
    }

    private function normalizeInput(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim($v);
        return $v === '' ? null : $v;
    }
}
