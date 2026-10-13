<?php

use App\Models\ActivityLog;
use App\Models\KpiQuarterApproval;
use App\Models\KpiQuarterValue;
use App\Models\KpiReportingDeadline;
use App\Models\ProvincialDirectorKPI;
use Illuminate\Support\Facades\Crypt;

// The quarterly KPI entry screen over HTTP: who can open it, who can save,
// who can approve, and what the page is given.

beforeEach(function () {
    $this->kpis     = makeCatalog();
    $this->region   = makeRegion();
    $this->province = makeProvince($this->region, 'Pangasinan', 'large');
    $this->director = makeUser('provincial_director', $this->province);
    $this->admin    = makeUser('provincial_admin', $this->province);
    $this->year     = (int) now()->year;
});

function entryPayload(array $targets = [], array $quarters = []): array
{
    $ids = fn(array $byCode) => collect($byCode)->mapWithKeys(fn($v, $code) => [test()->kpis[$code]->id => $v])->all();
    return ['targets' => $ids($targets), 'quarters' => collect($quarters)->map($ids)->all()];
}

function pageKpi(array $props, string $code): array
{
    return collect($props['kpi_categories'])->flatMap(fn($c) => $c['kpis'])->firstWhere('code', $code);
}

describe('access', function () {
    it('opens for the provincial admin, editable, on the current year', function () {
        $props = inertiaProps($this->actingAs($this->admin)->get('/provincial-kpi')
            ->assertOk()->assertInertia(fn($page) => $page->component('Kpi/Entry')));

        expect($props['province']['name'])->toBe('Pangasinan')
            ->and($props['year'])->toBe($this->year)
            ->and($props['can_edit'])->toBeTrue()
            ->and($props['can_approve'])->toBeFalse()
            ->and($props['is_central'])->toBeFalse()
            ->and($props['base_url'])->toBe('/provincial-kpi');
    });

    it('opens read-only with approval for the provincial director', function () {
        $props = inertiaProps($this->actingAs($this->director)->get('/provincial-kpi')->assertOk());
        expect($props['can_edit'])->toBeFalse()
            ->and($props['can_approve'])->toBeTrue();
    });

    it('is closed to other roles', function ($role) {
        $user = match ($role) {
            'regional_director' => makeUser($role, null, $this->region),
            'employee'          => makeUser($role, $this->province),
            default             => makeUser($role),
        };
        $this->actingAs($user)->get('/provincial-kpi')->assertForbidden();
    })->with(['super_admin', 'sub_admin', 'regional_director', 'employee']);

    it('refuses a provincial admin who has no province', function () {
        $orphan = makeUser('provincial_admin');
        $this->actingAs($orphan)->get('/provincial-kpi')->assertForbidden();
    });

    it('opens any province for central admins, from the KPI Data list', function ($role) {
        $id    = Crypt::encrypt($this->province->id);
        $props = inertiaProps($this->actingAs(makeUser($role))->get("/kpi-data/{$id}/2025")->assertOk());

        expect($props['province']['name'])->toBe('Pangasinan')
            ->and($props['year'])->toBe(2025)
            ->and($props['is_central'])->toBeTrue()
            ->and($props['can_edit'])->toBeTrue()
            ->and($props['base_url'])->toBe("/kpi-data/{$id}");
    })->with(['super_admin', 'sub_admin']);

    it('keeps provincial roles out of the central KPI Data screens', function () {
        $id = Crypt::encrypt($this->province->id);
        $this->actingAs($this->admin)->get('/kpi-data')->assertForbidden();
        $this->actingAs($this->admin)->get("/kpi-data/{$id}")->assertForbidden();
        $this->actingAs($this->admin)->put("/kpi-data/{$id}/{$this->year}", entryPayload())->assertForbidden();
    });
});

describe('page data', function () {
    it('describes how each KPI is entered', function () {
        $props = inertiaProps($this->actingAs($this->admin)->get('/provincial-kpi'));

        expect(pageKpi($props, 'core_proposals')['entry_type'])->toBe('entry')
            ->and(pageKpi($props, 'core_setup_delinquent_pct')['entry_type'])->toBe('derived')
            ->and(pageKpi($props, 'func_linkages_established')['entry_type'])->toBe('record')
            ->and(pageKpi($props, 'func_linkages_established')['module']['url'])->toBe("/linkages/{$this->director->id}/{$this->year}")
            ->and(pageKpi($props, 'supp_customer_satisfaction')['rollup_rule'])->toBe('average');
    });

    it('gives record KPIs no manage link when the province has no director', function () {
        $this->director->provinces()->detach();
        $props = inertiaProps($this->actingAs($this->admin)->get('/provincial-kpi'));
        expect(pageKpi($props, 'func_linkages_established')['module'])->toBeNull()
            ->and($props['director'])->toBeNull();
    });

    it('shows saved quarters, targets and the annual figure', function () {
        $this->actingAs($this->admin)->put("/provincial-kpi/{$this->year}",
            entryPayload(['core_proposals' => 40], [1 => ['core_proposals' => 12], 2 => ['core_proposals' => 15]]));

        $kpi = pageKpi(inertiaProps($this->actingAs($this->admin)->get('/provincial-kpi')), 'core_proposals');
        expect($kpi['target'])->toEqual(40)
            ->and($kpi['quarters'])->toEqual([1 => 12, 2 => 15, 3 => null, 4 => null])
            ->and($kpi['annual'])->toBe('27')
            ->and($kpi['annual_only'])->toBeFalse();
    });

    it('flags a year reported as a single annual figure', function () {
        setAnnual($this->province, $this->kpis['core_proposals'], 2025, 50, 45);
        $kpi = pageKpi(inertiaProps($this->actingAs($this->admin)->get('/provincial-kpi/2025')), 'core_proposals');
        expect($kpi['annual_only'])->toBeTrue()
            ->and($kpi['annual'])->toBe('45');
    });

    it('offers every year with data plus the current year', function () {
        setAnnual($this->province, $this->kpis['core_proposals'], 2023, 1, 1);
        $props = inertiaProps($this->actingAs($this->admin)->get('/provincial-kpi'));
        expect($props['years'])->toEqual([$this->year, 2023]);
    });

    it('reports each quarter\'s deadline, lock and approval', function () {
        KpiReportingDeadline::create(['year' => $this->year, 'quarter' => 1, 'deadline' => now()->subDay()->toDateString()]);
        KpiQuarterApproval::create(['province_id' => $this->province->id, 'year' => $this->year, 'quarter' => 2, 'approved_by' => $this->director->id]);

        $periods = collect(inertiaProps($this->actingAs($this->admin)->get('/provincial-kpi'))['periods'])->keyBy('quarter');
        expect($periods[1]['open'])->toBeFalse()
            ->and($periods[1]['deadline'])->toBe(now()->subDay()->toDateString())
            ->and($periods[2]['open'])->toBeTrue()
            ->and($periods[2]['approval']['by'])->not->toBeEmpty()
            ->and($periods[3]['approval'])->toBeNull();
    });
});

describe('saving', function () {
    it('saves the provincial admin\'s figures to their own province', function () {
        $this->actingAs($this->admin)
            ->from('/provincial-kpi')
            ->put("/provincial-kpi/{$this->year}", entryPayload(['core_proposals' => 40], [1 => ['core_proposals' => 12]]))
            ->assertRedirect('/provincial-kpi')
            ->assertSessionHas('success');

        $row = ProvincialDirectorKPI::where(['province_id' => $this->province->id, 'kpi_id' => $this->kpis['core_proposals']->id])->first();
        expect($row->target)->toBe('40')->and($row->accomplished)->toBe('12');
    });

    it('logs the change', function () {
        $this->actingAs($this->admin)->put("/provincial-kpi/{$this->year}", entryPayload(['core_proposals' => 1]));
        expect(ActivityLog::where('description', 'like', "%Pangasinan's {$this->year} KPI figures%")->exists())->toBeTrue();
    });

    it('lets central admins save any province', function () {
        $id = Crypt::encrypt($this->province->id);
        $this->actingAs(makeUser('super_admin'))->put("/kpi-data/{$id}/{$this->year}", entryPayload([], [3 => ['core_proposals' => 7]]))
            ->assertSessionHasNoErrors();
        expect(KpiQuarterValue::where(['province_id' => $this->province->id, 'quarter' => 3])->value('accomplished'))->toBe(7.0);
    });

    it('does not let the director save', function () {
        $this->actingAs($this->director)->put("/provincial-kpi/{$this->year}", entryPayload(['core_proposals' => 1]))
            ->assertForbidden();
        expect(ProvincialDirectorKPI::count())->toBe(0);
    });

    it('rejects values that are not non-negative numbers', function ($value) {
        $this->actingAs($this->admin)->put("/provincial-kpi/{$this->year}", entryPayload([], [1 => ['core_proposals' => $value]]))
            ->assertSessionHasErrors();
        expect(KpiQuarterValue::count())->toBe(0);
    })->with(['abc', -1, '12abc']);

    it('rejects a quarter outside 1–4', function () {
        $this->actingAs($this->admin)->put("/provincial-kpi/{$this->year}", ['quarters' => [5 => [$this->kpis['core_proposals']->id => 1]]])
            ->assertSessionHasErrors('quarters');
    });

    it('refuses a locked quarter with a message', function () {
        KpiReportingDeadline::create(['year' => $this->year, 'quarter' => 1, 'deadline' => now()->subDay()->toDateString()]);
        $this->actingAs($this->admin)->put("/provincial-kpi/{$this->year}", entryPayload([], [1 => ['core_proposals' => 3]]))
            ->assertSessionHasErrors(['quarters' => "Q1 {$this->year} is locked. Request a correction to change it."]);
    });
});

describe('director approval', function () {
    it('records the director\'s approval of a quarter', function () {
        $this->actingAs($this->director)->post("/provincial-kpi/{$this->year}/quarters/2/approve", ['note' => 'Checked'])
            ->assertSessionHas('success');

        $approval = KpiQuarterApproval::first();
        expect($approval->province_id)->toBe($this->province->id)
            ->and($approval->quarter)->toBe(2)
            ->and($approval->approved_by)->toBe($this->director->id)
            ->and($approval->note)->toBe('Checked');
    });

    it('approves only once per quarter', function () {
        $this->actingAs($this->director)->post("/provincial-kpi/{$this->year}/quarters/2/approve");
        $this->actingAs($this->director)->post("/provincial-kpi/{$this->year}/quarters/2/approve");
        expect(KpiQuarterApproval::count())->toBe(1);
    });

    it('is only for the director', function () {
        $this->actingAs($this->admin)->post("/provincial-kpi/{$this->year}/quarters/1/approve")->assertForbidden();
    });

    it('rejects a quarter outside 1–4', function () {
        $this->actingAs($this->director)->post("/provincial-kpi/{$this->year}/quarters/5/approve")->assertNotFound();
    });
});
