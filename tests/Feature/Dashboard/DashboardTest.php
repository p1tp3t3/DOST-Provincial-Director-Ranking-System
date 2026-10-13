<?php

use App\Services\RankingService;

// The shared Super Admin / Sub Admin / Regional Director dashboard, and the
// other roles' dashboards rendering.

beforeEach(function () {
    $this->kpis    = makeCatalog();
    $this->region  = makeRegion('Region I');
    $this->other   = makeRegion('Region II');
    $this->year    = 2025;

    // Six Small provinces (enough for buckets): two in Region I, four elsewhere.
    $this->provinces = [];
    foreach ([12, 10, 8, 6, 4, 2] as $i => $acc) {
        $p = makeProvince($i % 3 === 0 ? $this->region : $this->other, "P{$i}", 'small');
        makeUser('provincial_director', $p);
        setAnnual($p, $this->kpis['core_proposals'], $this->year, 10, $acc);
        $this->provinces[] = $p;
    }
});

function dashboardRows(array $props, $year = 2025): \Illuminate\Support\Collection
{
    return collect($props['rankings_by_year'][$year])->flatten(1);
}

it('gives super and sub admins the national dashboard', function ($role) {
    $props = inertiaProps($this->actingAs(makeUser($role))->get('/dashboard')
        ->assertOk()->assertInertia(fn($page) => $page->component('Admin/Dashboard/Main')));

    expect($props['scope'] ?? null)->toBeNull()
        ->and(dashboardRows($props))->toHaveCount(6)
        ->and(array_map('intval', $props['available_years']))->toBe([2025])
        ->and($props['kpi_categories'])->toHaveCount(3);
})->with(['super_admin', 'sub_admin']);

it('limits a regional director to their region, keeping national ranks', function () {
    $rd    = makeUser('regional_director', null, $this->region);
    $props = inertiaProps($this->actingAs($rd)->get('/dashboard')
        ->assertOk()->assertInertia(fn($page) => $page->component('Admin/Dashboard/Main')));

    $rows     = dashboardRows($props)->keyBy('province');
    $national = collect((new RankingService())->rankByYear($this->year)['small'])->keyBy('province');

    expect($props['scope'])->toBe(['region' => 'Region I'])
        ->and($rows->keys()->sort()->values()->all())->toBe(['P0', 'P3']);
    foreach ($rows as $name => $row) {
        expect([$row['rank'], $row['bucket'], $row['tier_size']])
            ->toBe([$national[$name]['rank'], $national[$name]['bucket'], 6]);
    }
});

it('attaches each province\'s submission progress', function () {
    setAnnual($this->provinces[0], $this->kpis['core_gross_sales'], $this->year, 0, 'n/a');      // neither counts
    setAnnual($this->provinces[0], $this->kpis['input_setup_ongoing_count'], $this->year, null, 3); // accomplishment only

    $props = inertiaProps($this->actingAs(makeUser('super_admin'))->get('/dashboard'));
    $row   = dashboardRows($props)->firstWhere('province', 'P0');

    // Entry KPIs: all but the derived one (7); scored entry KPIs need a target (5).
    expect($props['submission_expected'])->toBe(['accomplished' => 7, 'targets' => 5])
        ->and($row['submission'])->toBe(['accomplished' => 2, 'targets' => 1])
        ->and(dashboardRows($props)->firstWhere('province', 'P1')['submission'])->toBe(['accomplished' => 1, 'targets' => 1]);
});

it('gives each row category ranks and a link id', function () {
    $row = dashboardRows(inertiaProps($this->actingAs(makeUser('super_admin'))->get('/dashboard')))->firstWhere('province', 'P0');
    expect($row['category_ranks']['CORE']['rank'])->toBe(1)
        ->and($row['province_url_id'])->not->toBeEmpty()
        ->and(\Illuminate\Support\Facades\Crypt::decrypt($row['province_url_id']))->toBe($this->provinces[0]->id);
});

it('renders the provincial and employee dashboards', function ($role, $component) {
    $user = makeUser($role, $this->provinces[0]);
    $this->actingAs($user)->get('/dashboard')->assertOk()
        ->assertInertia(fn($page) => $page->component($component));
})->with([
    ['provincial_admin',    'ProvincialAdmin/Dashboard/Main'],
    ['employee',            'Employee/Dashboard/Main'],
]);

it('renders the provincial director dashboard', function () {
    $director = \App\Models\User::whereRole('provincial_director')->whereProvince($this->provinces[0]->id)->first();
    $this->actingAs($director)->get('/dashboard')->assertOk()
        ->assertInertia(fn($page) => $page->component('ProvincialDirector/Dashboard/Main'));
});

it('shows an empty dashboard when nothing has been submitted', function () {
    \App\Models\ProvincialDirectorKPI::query()->delete();
    $props = inertiaProps($this->actingAs(makeUser('super_admin'))->get('/dashboard')->assertOk());
    expect($props['available_years'])->toBe([])
        ->and($props['rankings_by_year'])->toBe([]);
});
