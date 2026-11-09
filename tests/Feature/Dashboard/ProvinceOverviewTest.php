<?php

use App\Models\KpiQuarterApproval;
use App\Models\KpiQuarterValue;
use App\Models\KpiReportingDeadline;
use App\Models\Linkage;
use App\Services\KpiEntryService;
use App\Services\ProvinceOverviewService;
use Illuminate\Support\Carbon;

// The provincial admin and director dashboards: standing, gaps, where points
// are lost, the tier comparison, the trend, and this year's reporting.

beforeEach(function () {
    $this->kpis   = makeCatalog();
    $this->region = makeRegion();
    $this->svc    = app(ProvinceOverviewService::class);

    // Six Small provinces on core_proposals (worth 40 points): 120%, 100%,
    // 76%, 50%, 11% and 10% of target score 40, 32, 24, 16, 8 and 0 points,
    // so the buckets are Top 1 / Average 4 / Low 1.
    $this->tier = [];
    foreach ([12, 10, 7.6, 5, 1.1, 1] as $i => $acc) {
        $p = makeProvince($this->region, 'P' . ($i + 1), 'small');
        makeUser('provincial_director', $p);
        setAnnual($p, $this->kpis['core_proposals'], 2025, 10, $acc);
        $this->tier[$i + 1] = $p;
    }
});

function overviewOf(int $position): array
{
    return test()->svc->performance(test()->tier[$position]->id);
}

describe('standing', function () {
    it('reports the official rank, tier size, group and score', function () {
        $standing = overviewOf(5)['standing'];
        expect($standing)->toMatchArray([
            'year' => 2025, 'status' => 'ranked', 'tier' => 'small',
            'rank' => 5, 'tier_size' => 6, 'bucket' => 'Average', 'total_pct' => 8.0,
        ]);
    });

    it('measures the gap to the next rank and the next group', function () {
        $low = overviewOf(6)['standing'];
        $avg = overviewOf(3)['standing'];

        expect($low['gap_to_next_rank'])->toBe(['rank' => 5, 'points' => round(overviewOf(5)['standing']['total_pct'] - $low['total_pct'], 2)])
            ->and($low['gap_to_next_group']['group'])->toBe('Average')
            ->and($low['gap_to_next_group']['points'])->toBe($low['gap_to_next_rank']['points'])   // #5 is the lowest Average
            ->and($avg['gap_to_next_group'])->toBe(['group' => 'Top', 'points' => round(40 - $avg['total_pct'], 2)]);
    });

    it('has no gaps at the top', function () {
        $top = overviewOf(1)['standing'];
        expect($top['gap_to_next_rank'])->toBeNull()
            ->and($top['gap_to_next_group'])->toBeNull()
            ->and($top['bucket'])->toBe('Top');
    });

    it('compares with the previous year', function () {
        setAnnual($this->tier[3], $this->kpis['core_proposals'], 2024, 10, 12);   // 2024: 40 pts, #1
        foreach ([1, 2] as $i) setAnnual($this->tier[$i], $this->kpis['core_proposals'], 2024, 10, 1);

        $standing = overviewOf(3)['standing'];
        expect($standing['previous_year'])->toBe(2024)
            ->and($standing['change'])->toBe(['points' => round($standing['total_pct'] - 40, 2), 'places' => -2]);
    });

    it('has no change without a ranked previous year', function () {
        expect(overviewOf(3)['standing']['change'])->toBeNull();
    });

    it('explains a province that is not ranked', function () {
        $p = makeProvince($this->region, 'NoData', 'small');
        makeUser('provincial_director', $p);

        $overview = $this->svc->performance($p->id);
        expect($overview['standing']['status'])->toBe('no_data')
            ->and($overview['improvement'])->toBe([])
            ->and($overview['versus_tier'])->toBeNull();
    });

    it('has no standing before any data exists', function () {
        \App\Models\ProvincialDirectorKPI::query()->delete();
        expect(overviewOf(1)['standing'])->toBeNull()
            ->and(overviewOf(1)['trend'])->toBe([]);
    });
});

describe('where to improve', function () {
    it('lists the KPIs losing the most points, biggest first', function () {
        // P3: core_proposals 76% (0.6 → 24 of 40), linkages 100% (0.8 → 24 of 30).
        setAnnual($this->tier[3], $this->kpis['func_linkages_established'], 2025, 4, 4);

        $improvement = overviewOf(3)['improvement'];
        $byCode = collect($improvement)->keyBy(fn($k) => \App\Models\KPI::find($k['kpi_id'])->code);

        expect($improvement[0]['missed'])->toBeGreaterThanOrEqual($improvement[1]['missed'])
            ->and($byCode['core_proposals']['missed'])->toBe(16.0)
            ->and($byCode['core_proposals']['possible'])->toBe(40.0)
            ->and($byCode['core_proposals']['target'])->toBe('10')
            ->and($byCode['core_proposals']['accomplished'])->toBe('7.6')
            ->and($byCode['func_linkages_established']['missed'])->toBe(6.0)
            ->and(count($improvement))->toBeLessThanOrEqual(5);
    });

    it('leaves out KPIs that earned full points', function () {
        $codes = collect(overviewOf(1)['improvement'])->map(fn($k) => \App\Models\KPI::find($k['kpi_id'])->code);
        expect($codes)->not->toContain('core_proposals'); // P1 is Outstanding on it
    });
});

describe('versus tier and trend', function () {
    it('puts the province beside its tier\'s average and best', function () {
        $versus = overviewOf(4)['versus_tier'];
        expect($versus['size'])->toBe(6)
            ->and($versus['total']['mine'])->toBe(overviewOf(4)['standing']['total_pct'])
            ->and($versus['total']['tier_best'])->toBe(40.0)
            ->and($versus['total']['tier_average'])->toBe(20.0)
            ->and(collect($versus['categories'])->pluck('code')->all())->toBe(['CORE', 'STRATEGIC', 'SUPPORT']);
    });

    it('lists ranked years oldest first', function () {
        setAnnual($this->tier[1], $this->kpis['core_proposals'], 2023, 10, 10);
        $trend = overviewOf(1)['trend'];
        expect(array_column($trend, 'year'))->toBe([2023, 2025])
            ->and($trend[0])->toMatchArray(['rank' => 1, 'tier_size' => 1]);
    });
});

describe('reporting', function () {
    beforeEach(function () {
        Carbon::setTestNow('2026-05-20 10:00:00');   // Q2
        $this->province = $this->tier[1];
        $this->admin    = makeUser('provincial_admin', $this->province);
    });
    afterEach(fn() => Carbon::setTestNow());

    function enter(array $quarters, array $targets = []): void
    {
        $ids = fn($byCode) => collect($byCode)->mapWithKeys(fn($v, $c) => [test()->kpis[$c]->id => $v])->all();
        app(KpiEntryService::class)->saveYear(test()->province->id, 2026, $ids($targets), collect($quarters)->map($ids)->all(), test()->admin);
    }

    it('focuses on the calendar quarter when no deadlines are set', function () {
        $r = $this->svc->reporting($this->province->id);
        expect($r['year'])->toBe(2026)
            ->and($r['focus_quarter'])->toBe(2)
            ->and($r['focus_deadline'])->toBeNull()
            ->and($r['expected'])->toBe(['accomplished' => 5, 'targets' => 5]);
    });

    it('focuses on the open quarter with the nearest deadline', function () {
        KpiReportingDeadline::create(['year' => 2026, 'quarter' => 1, 'deadline' => '2026-04-30']);   // passed
        KpiReportingDeadline::create(['year' => 2026, 'quarter' => 2, 'deadline' => '2026-07-15']);
        KpiReportingDeadline::create(['year' => 2026, 'quarter' => 3, 'deadline' => '2026-10-15']);

        $r = $this->svc->reporting($this->province->id);
        expect($r['focus_quarter'])->toBe(2)
            ->and($r['focus_deadline'])->toBe('2026-07-15')
            ->and($r['days_left'])->toBe(56)
            ->and($r['quarters'][0]['open'])->toBeFalse();
    });

    it('counts entered figures, targets and approvals per quarter', function () {
        enter([1 => ['core_proposals' => 3, 'core_gross_sales' => 0], 2 => ['core_proposals' => 1]],
              ['core_proposals' => 10, 'core_gross_sales' => 0, 'func_linkages_established' => 4]);
        // approved_by is a user (the province's director), not a province id.
        $director = \App\Models\User::whereRole('provincial_director')->whereProvince($this->province->id)->first();
        KpiQuarterApproval::create(['province_id' => $this->province->id, 'year' => 2026, 'quarter' => 1, 'approved_by' => $director->id]);

        $r = $this->svc->reporting($this->province->id);
        expect($r['quarters'][0])->toMatchArray(['entered' => 2, 'has_data' => true])
            ->and($r['quarters'][0]['approved_by'])->not->toBeNull()
            ->and($r['quarters'][1])->toMatchArray(['entered' => 1, 'approved_by' => null])
            ->and($r['quarters'][2]['has_data'])->toBeFalse()
            ->and($r['targets_set'])->toBe(2);   // a zero target doesn't count
    });

    it('raises the reporting gaps worth acting on', function () {
        KpiReportingDeadline::create(['year' => 2026, 'quarter' => 1, 'deadline' => '2026-04-30']);
        enter([2 => ['core_proposals' => 1]]);

        $items = collect($this->svc->attention($this->svc->reporting($this->province->id), true))->pluck('text');
        expect($items)->toContain('Q1 closed with 5 KPIs left blank.')
            ->toContain('4 KPIs still need a Q2 figure.')
            ->toContain('5 of 5 2026 targets aren\'t set yet.')
            ->toContain("Q2 is waiting for the director's approval.");
    });

    it('flags a vacant director post instead of approvals', function () {
        enter([2 => ['core_proposals' => 1]]);
        $items = collect($this->svc->attention($this->svc->reporting($this->province->id), false));

        expect($items->first()['level'])->toBe('error')
            ->and($items->pluck('text')->implode(' '))->not->toContain('waiting for the director');
    });

    it('is all caught up when the quarter, targets and approvals are done', function () {
        $all = ['core_proposals' => 1, 'core_gross_sales' => 1, 'input_setup_ongoing_count' => 1, 'input_setup_delinquent_count' => 0, 'supp_customer_satisfaction' => 4];
        enter([2 => $all], ['core_proposals' => 1, 'core_gross_sales' => 1, 'func_linkages_established' => 1, 'supp_facebook_posts' => 1, 'supp_customer_satisfaction' => 5]);
        KpiQuarterApproval::create(['province_id' => $this->province->id, 'year' => 2026, 'quarter' => 2, 'approved_by' => $this->admin->id]);

        expect($this->svc->attention($this->svc->reporting($this->province->id), true))->toBe([]);
    });

    it('lists the latest entries with who made them', function () {
        enter([1 => ['core_proposals' => 3]]);
        Carbon::setTestNow('2026-05-21 10:00:00');
        enter([2 => ['core_gross_sales' => 7]]);

        $entries = $this->svc->recentEntries($this->province->id);
        expect($entries)->toHaveCount(2)
            ->and($entries[0]['quarter'])->toBe(2)
            ->and($entries[0]['accomplished'])->toBe(7.0)
            ->and($entries[0]['by'])->not->toBe('System')
            // A timezone-qualified timestamp (the app runs on Asia/Manila), so a
            // browser reads the same moment wherever it is.
            ->and($entries[0]['at'])->toBe('2026-05-21T10:00:00+08:00');
    });

    it('counts this year\'s evidence records and accounts', function () {
        $director = \App\Models\User::whereRole('provincial_director')->whereProvince($this->province->id)->first();
        Linkage::create(['province_id' => $this->province->id, 'provincial_director_id' => $director->id, 'year' => 2026,
            'partner_organization' => 'DTI', 'title' => 'MOA', 'type' => 'MOA', 'date_signed' => '2026-02-01']);
        makeUser('employee', $this->province)->forceFill(['activate' => false])->save();

        $records = $this->svc->records($this->province->id, $director->id);
        $staff   = $this->svc->staff($this->province->id, $director);

        expect($records)->toMatchArray(['linkages' => 1, 'facebook_posts' => 0, 'linkages_url' => "/linkages/{$director->id}/2026"])
            ->and($staff)->toMatchArray(['accounts' => 3, 'staff' => 1, 'deactivated' => 1])
            ->and($staff['director'])->not->toBeNull();
    });
});

describe('dashboards', function () {
    it('gives the provincial admin the reporting dashboard', function () {
        $admin = makeUser('provincial_admin', $this->tier[2]);
        $props = inertiaProps($this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertInertia(fn($page) => $page->component('ProvincialAdmin/Dashboard/Main')));

        expect(array_keys($props))->toContain('reporting', 'attention', 'records', 'recent_entries', 'staff', 'standing')
            ->and($props['standing']['rank'])->toBe(2)
            ->and($props['province']['name'])->toBe('P2');
    });

    it('gives the provincial director the standing dashboard', function () {
        $director = \App\Models\User::whereRole('provincial_director')->whereProvince($this->tier[2]->id)->first();
        KpiQuarterValue::create(['province_id' => $this->tier[2]->id, 'kpi_id' => $this->kpis['core_proposals']->id,
            'year' => (int) now()->year, 'quarter' => 1, 'accomplished' => 3]);

        $props = inertiaProps($this->actingAs($director)->get('/dashboard')->assertOk()
            ->assertInertia(fn($page) => $page->component('ProvincialDirector/Dashboard/Main')));

        expect($props['standing']['rank'])->toBe(2)
            ->and($props['versus_tier']['size'])->toBe(6)
            ->and($props['categories'])->toHaveCount(3)
            ->and($props['awaiting_approval']['quarters'])->toBe([['quarter' => 1, 'entered' => 1]]);
    });

    it('refuses a provincial account without a province', function ($role) {
        $this->actingAs(makeUser($role))->get('/dashboard')->assertForbidden();
    })->with(['provincial_admin', 'provincial_director']);
});
