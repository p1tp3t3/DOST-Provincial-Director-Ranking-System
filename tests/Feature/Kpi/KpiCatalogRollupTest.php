<?php

use App\Models\KPICategory;
use App\Models\KpiQuarterValue;
use App\Models\ProvincialDirectorKPI;
use App\Services\KpiEntryService;

// Each KPI's roll-up rule is set on the KPI Catalog page.

beforeEach(function () {
    $this->kpis  = makeCatalog();
    $this->admin = makeUser('super_admin');
});

function catalogChange(\App\Models\KPI $kpi, array $overrides = []): array
{
    return ['changes' => [[
        'id' => $kpi->id, 'name' => $kpi->name, 'category_id' => $kpi->category_id,
        'weight' => round($kpi->weight * 100, 4), ...$overrides,
    ]]];
}

it('includes each KPI\'s rule in the catalog', function () {
    $kpis = collect(KPICategory::catalog())->flatMap(fn($c) => $c['kpis'])->keyBy('code');
    expect($kpis['core_proposals']['rollup_rule'])->toBe('sum')
        ->and($kpis['input_setup_ongoing_count']['rollup_rule'])->toBe('latest')
        ->and($kpis['supp_customer_satisfaction']['rollup_rule'])->toBe('average');
});

it('changes a rule and re-rolls provinces that report quarterly', function () {
    $province = makeProvince();
    $admin    = makeUser('provincial_admin', $province);
    $kpi      = $this->kpis['core_gross_sales'];
    app(KpiEntryService::class)->saveYear($province->id, 2026, [], [1 => [$kpi->id => 10], 2 => [$kpi->id => 4]], $admin);

    $this->actingAs($this->admin)->put('/kpi-catalog/kpis', catalogChange($kpi, ['rollup_rule' => 'average']))
        ->assertSessionHasNoErrors();

    expect($kpi->fresh()->rollup_rule)->toBe('average')
        ->and(ProvincialDirectorKPI::where('kpi_id', $kpi->id)->value('accomplished'))->toBe('7');
});

it('leaves the rule alone when a change does not mention it', function () {
    $kpi = $this->kpis['supp_customer_satisfaction'];
    $this->actingAs($this->admin)->put('/kpi-catalog/kpis', catalogChange($kpi, ['name' => 'Customer satisfaction']))
        ->assertSessionHasNoErrors();

    expect($kpi->fresh()->rollup_rule)->toBe('average')
        ->and($kpi->fresh()->name)->toBe('Customer satisfaction');
});

it('rejects an unknown rule', function () {
    $this->actingAs($this->admin)->put('/kpi-catalog/kpis', catalogChange($this->kpis['core_proposals'], ['rollup_rule' => 'median']))
        ->assertSessionHasErrors('changes.0.rollup_rule');
    expect($this->kpis['core_proposals']->fresh()->rollup_rule)->toBe('sum');
});

it('is a central-admin action', function () {
    $this->actingAs(makeUser('provincial_admin', makeProvince()))
        ->put('/kpi-catalog/kpis', catalogChange($this->kpis['core_proposals'], ['rollup_rule' => 'latest']))
        ->assertForbidden();
});
