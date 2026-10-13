<?php

use App\Models\KPI;
use App\Models\KPICategory;
use App\Models\Province;
use App\Models\ProvincialDirectorKPI;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Test data
|--------------------------------------------------------------------------
|
| The factories for provinces and KPIs are empty, so feature tests build
| their world with these helpers: regions, provinces, a small KPI catalog
| with the same kinds of KPI as the real matrix, users holding a role (with
| the profile every real account has), and annual KPI figures.
|
*/

function makeRegion(string $name = 'Region I', string $island = 'luzon'): Region
{
    return Region::create(['name' => $name, 'island_under' => $island]);
}

function makeProvince(?Region $region = null, ?string $name = null, string $category = 'small'): Province
{
    $region ??= Region::first() ?? makeRegion();

    return Province::create([
        'name'                    => $name ?? 'Province ' . fake()->unique()->numberBetween(1, 99999),
        'category'                => $category,
        'region_id'               => $region->id,
        'num_plantilla_employees' => 0,
        'num_municipalities'      => 0,
        'num_cities'              => 0,
    ]);
}

// A user holding $role, attached to $province (before the role, so the
// one-director-per-province rule sees it) or scoped to $region.
function makeUser(string $role, ?Province $province = null, ?Region $region = null, array $attributes = []): User
{
    $user = User::factory()->create(['region_id' => $region?->id, ...$attributes]);
    if ($province) {
        $user->provinces()->attach($province->id);
    }
    $user->assignRole($role);
    $user->ensureEmployeeProfile();

    return $user->fresh();
}

// Three weighted categories and one KPI of every kind the real matrix has:
//   counted (sum), a rating (average), the derived % Delinquent SETUP with its
//   two latest-value inputs, and the two record-backed KPIs.
// Scored weights add up to 1.0. Returns the KPIs keyed by code.
function makeCatalog(): array
{
    $core      = KPICategory::create(['code' => 'CORE',      'name' => 'Core',      'weight' => 0.60, 'sort_order' => 1]);
    $strategic = KPICategory::create(['code' => 'STRATEGIC', 'name' => 'Strategic', 'weight' => 0.30, 'sort_order' => 2]);
    $support   = KPICategory::create(['code' => 'SUPPORT',   'name' => 'Support',   'weight' => 0.10, 'sort_order' => 3]);

    $rows = [
        ['core_proposals',               $core,      0.40, true,  false, null,               'sum'],
        ['core_gross_sales',             $core,      0.10, true,  false, null,               'sum'],
        ['core_setup_delinquent_pct',    $core,      0.10, true,  true,  'delinquent_ratio', 'sum'],
        ['input_setup_ongoing_count',    $core,      0.00, false, false, null,               'latest'],
        ['input_setup_delinquent_count', $core,      0.00, false, false, null,               'latest'],
        ['func_linkages_established',    $strategic, 0.30, true,  false, null,               'sum'],
        ['supp_facebook_posts',          $support,   0.05, true,  false, null,               'sum'],
        ['supp_customer_satisfaction',   $support,   0.05, true,  false, null,               'average'],
    ];

    $kpis = [];
    foreach ($rows as $i => [$code, $category, $weight, $scored, $inverse, $derivation, $rule]) {
        $kpis[$code] = KPI::create([
            'category_id'     => $category->id,
            'code'            => $code,
            'name'            => ucfirst(str_replace('_', ' ', $code)),
            'weight'          => $weight,
            'is_scored'       => $scored,
            'inverse_scoring' => $inverse,
            'derivation_type' => $derivation,
            'rollup_rule'     => $rule,
            'sort_order'      => $i + 1,
        ]);
    }

    return $kpis;
}

// An annual figure as the ranking reads it.
function setAnnual(Province $province, KPI $kpi, int $year, $target, $accomplished): ProvincialDirectorKPI
{
    return ProvincialDirectorKPI::create([
        'province_id'  => $province->id,
        'kpi_id'       => $kpi->id,
        'year'         => $year,
        'target'       => $target === null ? null : (string) $target,
        'accomplished' => $accomplished === null ? null : (string) $accomplished,
    ]);
}

// The props an Inertia response rendered.
function inertiaProps($response): array
{
    return $response->viewData('page')['props'];
}
