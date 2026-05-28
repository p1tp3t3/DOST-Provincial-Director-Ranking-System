<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProvinceCreationRequest;
use App\Http\Resources\ProvinceProfileResource;
use App\Http\Resources\ProvinceResource;
use App\Models\KPI;
use App\Models\Province;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ProvinceController extends Controller
{
    public function index() {
        $data = Province::with(['provincialDirector.profile', 'user'])->get();
        return inertia("Other/Province/Main", [
            'provinces' => ProvinceResource::collection($data)
        ]);
    }

    public function province_profile_index($id) {
        $decryptedId = Crypt::decrypt($id);

        $data = Province::with(['provincialDirector.profile', 'user.profile.employeeProfile'])
                        ->has('user.profile.employeeProfile')
                        ->whereHas('user', function ($q) {
                            $q->where('role', 'employee');
                        })
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

        // Score lookup: subrow_id → year → {target, accomplished}
        $scoreMap = [];
        if ($directorId) {
            foreach (DB::table('provincial_director_kpis')->where('provincial_director_id', $directorId)->get() as $s) {
                $scoreMap[$s->kpi_subrow_id][(string) $s->year] = [
                    'target'       => $s->target,
                    'accomplished' => $s->accomplished,
                ];
            }
        }

        $kpis = KPI::with('subRows')->orderBy('id')->get()
            ->map(fn ($kpi) => [
                'id'            => $kpi->id,
                'outcome_title' => $kpi->outcome_title,
                'subrows'       => $kpi->subRows
                    ->map(fn ($row) => [
                        'id'          => $row->id,
                        'description' => $row->description,
                        'scores'      => $scoreMap[$row->id] ?? [],
                    ])
                    ->values()
                    ->toArray(),
            ])
            ->values()
            ->toArray();

        return inertia('Other/Province/Profile', [
            'province_profile' => ProvinceProfileResource::collection($data),
            'kpis'             => $kpis,
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

        User::create([
            'role'        => 'provincial_admin',
            'province_id' => $province->id,
            'username'    => $data['admin_username'],
            'email'       => $data['admin_email'],
            'password'    => bcrypt($data['admin_password']),
        ]);

        return redirect()->back();
    }
}
