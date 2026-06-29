<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\KPI;
use App\Models\KPICategory;
use App\Models\KpiEditRequest;
use App\Models\KpiEditUsage;
use App\Models\Province;
use App\Models\ProvincialDirectorKPI;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

// Super-admin-only editor for the PSTD Ranking Matrix accomplishment data.
// Lives at /kpi-data and is intentionally separate from the public-facing
// Province Directories page (which stays read-only).
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
            'province'           => [
                'id'       => Crypt::encrypt($province->id),
                'name'     => $province->name,
                'category' => $province->category,
            ],
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
        ]);

        $director = User::where('id', $directorId)
            ->where('role', 'provincial_director')->firstOrFail();

        $modularKpiIds = KPI::whereIn('code', array_keys(self::MODULAR_KPI_CODES))->pluck('id')->all();

        DB::transaction(function () use ($request, $director, $year, $modularKpiIds) {
            foreach ($request->input('values') as $row) {
                $target       = $this->normalizeInput($row['target']       ?? null);
                $accomplished = $this->normalizeInput($row['accomplished'] ?? null);
                $isModular    = in_array((int) $row['kpi_id'], $modularKpiIds, true);

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

        return back()->with('success', "Saved KPI data for {$year}.");
    }

    // ── Provincial Sub Admin ──────────────────────────────────────────────────

    public function provincial_index(?int $year = null)
    {
        $user       = Auth::user();
        $provinceId = $user->province_id;

        $director = User::query()
            ->whereProvince($provinceId)
            ->where('role', 'provincial_director')
            ->with('profile')
            ->first();

        if (!$director) {
            return inertia('ProvincialSubAdmin/KPI/Main', ['no_director' => true]);
        }

        $svc            = new RankingService();
        $availableYears = $svc->availableYears();
        $year           = $year ?: ($availableYears[0] ?? now()->year);

        $directorYears = DB::table('provincial_director_kpis')
            ->where('provincial_director_id', $director->id)
            ->distinct()->orderByDesc('year')->pluck('year')->values()->toArray();

        $values = ProvincialDirectorKPI::query()
            ->where('provincial_director_id', $director->id)
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

        $profile      = $director->profile;
        $directorName = $profile
            ? trim(($profile->first_name ?? '') . ' ' . ($profile->middle_name ?? '') . ' ' . ($profile->last_name ?? ''))
            : '—';

        return inertia('ProvincialSubAdmin/KPI/Main', [
            'director'          => ['id' => $director->id, 'name' => $directorName ?: '—'],
            'year'              => (int) $year,
            'available_years'   => $availableYears,
            'director_years'    => $directorYears,
            'kpi_categories'    => $categories,
            'modular_kpi_codes' => self::MODULAR_KPI_CODES,
            'no_director'       => false,
            'edit_access'       => $this->editLockState($director->id, (int) $year),
        ]);
    }

    public function provincial_update(Request $request, int $directorId, int $year)
    {
        $director = $this->ownDirectorOrFail($directorId);

        $request->validate([
            'values'                 => ['required', 'array'],
            'values.*.kpi_id'        => ['required', 'integer', 'exists:kpis,id'],
            'values.*.target'        => ['nullable', 'string', 'max:255'],
            'values.*.accomplished'  => ['nullable', 'string', 'max:255'],
        ]);

        $modularKpiIds = KPI::whereIn('code', array_keys(self::MODULAR_KPI_CODES))->pluck('id')->all();
        $rows          = $request->input('values');

        try {
            DB::transaction(function () use ($director, $year, $rows, $modularKpiIds) {
                $this->consumeEditRight($director->id, $year);

                foreach ($rows as $row) {
                    $kpiId        = (int) $row['kpi_id'];
                    $isModular    = in_array($kpiId, $modularKpiIds, true);
                    $target       = $this->normalizeInput($row['target']       ?? null);
                    $accomplished = $this->normalizeInput($row['accomplished'] ?? null);

                    // For modular KPIs, accomplishment is owned by the module — never
                    // touch it from this form. Only target is editable here.
                    if ($isModular) {
                        if ($target === null) {
                            ProvincialDirectorKPI::where('provincial_director_id', $director->id)
                                ->where('kpi_id', $kpiId)->where('year', $year)
                                ->update(['target' => null]);
                            continue;
                        }
                        ProvincialDirectorKPI::updateOrCreate(
                            ['provincial_director_id' => $director->id, 'kpi_id' => $kpiId, 'year' => $year],
                            ['target' => $target]
                        );
                        continue;
                    }

                    if ($target === null && $accomplished === null) {
                        ProvincialDirectorKPI::where('provincial_director_id', $director->id)
                            ->where('kpi_id', $kpiId)->where('year', $year)->delete();
                        continue;
                    }

                    ProvincialDirectorKPI::updateOrCreate(
                        ['provincial_director_id' => $director->id, 'kpi_id' => $kpiId, 'year' => $year],
                        ['target' => $target, 'accomplished' => $accomplished]
                    );
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['edit_access' => $e->getMessage()]);
        }

        return back()->with('success', "KPI data saved for {$year}.");
    }

    public function provincial_request_access(Request $request, int $directorId, int $year)
    {
        $director = $this->ownDirectorOrFail($directorId);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $alreadyPending = KpiEditRequest::where('provincial_director_id', $director->id)
            ->where('year', $year)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return back()->withErrors(['edit_access' => 'You already have a pending request for this.']);
        }

        KpiEditRequest::create([
            'provincial_director_id' => $director->id,
            'requested_by'           => Auth::id(),
            'year'                   => $year,
            'reason'                 => $data['reason'] ?? null,
            'status'                 => 'pending',
        ]);

        return back()->with('success', 'Request sent to your regional admin.');
    }

    private function ownDirectorOrFail(int $directorId): User
    {
        return User::query()
            ->where('id', $directorId)
            ->where('role', 'provincial_director')
            ->whereProvince(Auth::user()->province_id)
            ->firstOrFail();
    }

    // Whether the free edit for this (director, year) is still available, or —
    // once spent — whether an approved-and-unused request has granted one more.
    // Surfaced to the Vue editor so it can disable inputs and show request status.
    private function editLockState(int $directorId, int $year): array
    {
        $used = KpiEditUsage::where('provincial_director_id', $directorId)
            ->where('year', $year)->exists();

        if (!$used) {
            return ['locked' => false, 'pending_request' => null, 'last_request' => null];
        }

        $hasUnusedGrant = KpiEditRequest::where('provincial_director_id', $directorId)
            ->where('year', $year)
            ->where('status', 'approved')->whereNull('used_at')
            ->exists();

        $pending = KpiEditRequest::where('provincial_director_id', $directorId)
            ->where('year', $year)
            ->where('status', 'pending')->latest()->first();

        $last = KpiEditRequest::where('provincial_director_id', $directorId)
            ->where('year', $year)
            ->whereIn('status', ['approved', 'rejected'])
            ->latest()->first();

        return [
            'locked'          => !$hasUnusedGrant,
            'pending_request' => $pending ? ['id' => $pending->id, 'created_at' => $pending->created_at->toIso8601String()] : null,
            'last_request'    => $last ? [
                'id'            => $last->id,
                'status'        => $last->status,
                'response_note' => $last->response_note,
                'used'          => $last->used_at !== null,
            ] : null,
        ];
    }

    // Consumes the free edit (or, once spent, an approved-and-unused grant) for this
    // (director, year). Throws if neither is available — callers should run this
    // inside the same transaction as the actual data write, before it.
    private function consumeEditRight(int $directorId, int $year): void
    {
        $used = KpiEditUsage::where('provincial_director_id', $directorId)
            ->where('year', $year)->exists();

        $requestId = null;

        if ($used) {
            $grant = KpiEditRequest::where('provincial_director_id', $directorId)
                ->where('year', $year)
                ->where('status', 'approved')->whereNull('used_at')
                ->lockForUpdate()->first();

            if (!$grant) {
                throw new \RuntimeException(
                    "Your free edit for {$year} has already been used. Request additional access from your regional admin."
                );
            }

            $grant->update(['used_at' => now()]);
            $requestId = $grant->id;
        }

        KpiEditUsage::create([
            'provincial_director_id' => $directorId,
            'year'                   => $year,
            'used_by'                => Auth::id(),
            'kpi_edit_request_id'    => $requestId,
        ]);
    }

    private function normalizeInput(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim($v);
        return $v === '' ? null : $v;
    }
}
