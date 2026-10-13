<?php

use App\Models\ActivityLog;
use App\Models\KpiReportingDeadline;

// Central office sets the per-quarter reporting deadlines on the KPI Data page.

beforeEach(function () {
    makeCatalog();
    $this->admin = makeUser('super_admin');
});

it('sets and clears a year\'s deadlines', function () {
    $this->actingAs($this->admin)
        ->put('/kpi-deadlines/2026', ['deadlines' => [1 => '2026-04-15', 2 => '2026-07-15', 3 => null, 4 => '2027-01-15']])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    expect(KpiReportingDeadline::where('year', 2026)->orderBy('quarter')->get()->map(fn($d) => [$d->quarter, $d->deadline->toDateString()])->all())
        ->toBe([[1, '2026-04-15'], [2, '2026-07-15'], [4, '2027-01-15']]);

    $this->actingAs($this->admin)->put('/kpi-deadlines/2026', ['deadlines' => [1 => '2026-04-30', 2 => null, 3 => null, 4 => null]]);

    expect(KpiReportingDeadline::where('year', 2026)->pluck('quarter')->all())->toBe([1])
        ->and(KpiReportingDeadline::first()->deadline->toDateString())->toBe('2026-04-30')
        ->and(KpiReportingDeadline::first()->set_by)->toBe($this->admin->id);
});

it('logs who set the deadlines', function () {
    $this->actingAs($this->admin)->put('/kpi-deadlines/2026', ['deadlines' => [1 => '2026-04-15']]);
    expect(ActivityLog::where('description', 'like', '%set the 2026 reporting deadlines%')->exists())->toBeTrue();
});

it('rejects an invalid date', function () {
    $this->actingAs($this->admin)->put('/kpi-deadlines/2026', ['deadlines' => [1 => 'not a date']])
        ->assertSessionHasErrors('deadlines.1');
    expect(KpiReportingDeadline::count())->toBe(0);
});

it('is open to central admins only', function () {
    $province = makeProvince();
    $this->actingAs(makeUser('sub_admin'))->put('/kpi-deadlines/2026', ['deadlines' => [1 => '2026-04-15']])->assertSessionHasNoErrors();
    $this->actingAs(makeUser('provincial_admin', $province))->put('/kpi-deadlines/2026', ['deadlines' => [1 => '2026-01-01']])->assertForbidden();
    $this->actingAs(makeUser('regional_director', null, $province->region))->put('/kpi-deadlines/2026', ['deadlines' => [1 => '2026-01-01']])->assertForbidden();

    expect(KpiReportingDeadline::first()->deadline->toDateString())->toBe('2026-04-15');
});

it('shows the deadlines on the KPI Data list', function () {
    KpiReportingDeadline::create(['year' => 2026, 'quarter' => 2, 'deadline' => '2026-07-15']);
    $province = makeProvince(null, 'Abra');
    setAnnual($province, \App\Models\KPI::first(), 2024, 1, 1);

    $props = inertiaProps($this->actingAs($this->admin)->get('/kpi-data')->assertOk());
    $years = array_map('intval', $props['deadline_years']);

    expect($props['deadlines'][2026][2])->toBe('2026-07-15')
        ->and($years)->toContain((int) now()->year, (int) now()->year + 1, 2024)
        ->and(collect($props['provinces'])->firstWhere('name', 'Abra')['years'])->toBe(['2024']);
});

it('lists provinces even without a director', function () {
    makeProvince(null, 'Vacant');
    $row = collect(inertiaProps($this->actingAs($this->admin)->get('/kpi-data'))['provinces'])->firstWhere('name', 'Vacant');
    expect($row['director'])->toBeNull()
        ->and($row['id'])->not->toBeEmpty();
});
