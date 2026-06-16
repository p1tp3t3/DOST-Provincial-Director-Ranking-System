<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProvinceCreationRequest;
use App\Http\Resources\ProvinceProfileResource;
use App\Http\Resources\ProvinceResource;
use App\Models\KPI;
use App\Models\KPICategory;
use App\Models\Province;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ProvinceController extends Controller
{
    public function index() {
        $query = Province::with(['directorAssignments.profile', 'users']);

        if (auth()->user()->role === 'regional_admin') {
            $region = Region::with('provinces')->findOrFail(auth()->user()->region_id);
            $query->whereIn('id', $region->provinces->pluck('id'));
        }

        $data = $query->get();
        return inertia("Other/Province/Main", [
            'provinces' => ProvinceResource::collection($data)
        ]);
    }

    public function province_profile_index($id) {
        $decryptedId = Crypt::decrypt($id);

        $data = Province::with([
                            'directorAssignments.profile',
                            'users' => fn($q) => $q->where('role', 'employee')->with('profile.employeeProfile'),
                        ])
                        ->where('id', $decryptedId)
                        ->get();

        $directorId = $data->first()?->provincialDirector?->id;

        // Available years this director has KPI data for
        $availableYears = $directorId
            ? DB::table('provincial_director_kpis')
                ->where('provincial_director_id', $directorId)
                ->orderByDesc('year')
                ->distinct()
                ->pluck('year')
                ->values()
                ->toArray()
            : [];

        // Score lookup: kpi_id → year → {target, accomplished}
        $scoreMap = [];
        if ($directorId) {
            foreach (DB::table('provincial_director_kpis')->where('provincial_director_id', $directorId)->get() as $s) {
                $scoreMap[$s->kpi_id][(string) $s->year] = [
                    'target'       => $s->target,
                    'accomplished' => $s->accomplished,
                ];
            }
        }

        // Group KPIs under their matrix category (CORE / FUNCTIONAL / SUPPORT) for display
        $categories = KPICategory::with(['kpis' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')->get()
            ->map(fn($cat) => [
                'id'     => $cat->id,
                'code'   => $cat->code,
                'name'   => $cat->name,
                'weight' => (float) $cat->weight,
                'kpis'   => $cat->kpis->map(fn($kpi) => [
                    'id'              => $kpi->id,
                    'code'            => $kpi->code,
                    'name'            => $kpi->name,
                    'weight'          => (float) $kpi->weight,
                    'is_scored'       => $kpi->is_scored,
                    'inverse_scoring' => $kpi->inverse_scoring,
                    'scores'          => $scoreMap[$kpi->id] ?? [],
                ])->values()->toArray(),
            ])
            ->values()->toArray();

        return inertia('Other/Province/Profile', [
            'province_profile' => ProvinceProfileResource::collection($data),
            'kpi_categories'   => $categories,
            'available_years'  => $availableYears,
        ]);
    }

    public function store(ProvinceCreationRequest $request) {
        $data = $request->validated();

        $province = Province::create([
            'name'                    => $data['name'],
            'category'                => $data['category'],
            'num_plantilla_employees' => 0,
            'num_municipalities'      => 0,
            'num_cities'              => 0,
        ]);

        $admin = User::create([
            'role'        => 'provincial_admin',
            'username'    => $data['admin_username'],
            'email'       => $data['admin_email'],
            'password'    => bcrypt($data['admin_password']),
        ]);

        $admin->provinces()->attach($province->id);

        return redirect()->back();
    }
}
