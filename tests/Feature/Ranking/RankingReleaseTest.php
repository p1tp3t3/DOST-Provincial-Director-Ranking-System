<?php

use App\Models\KpiQuarterApproval;
use App\Models\KpiReportingDeadline;
use App\Models\User;
use App\Services\KpiEntryService;
use App\Services\RankingRelease;
use Illuminate\Support\Carbon;

// A year's rankings stay hidden until every province has submitted it (its
// director approved all four quarters) or its Q4 deadline has passed.

beforeEach(function () {
    Carbon::setTestNow('2026-06-15 09:00:00');

    $this->kpis   = makeCatalog();
    $this->region = makeRegion('Region I');
    $this->provinces = [];
    foreach (['Abra', 'Benguet', 'Kalinga'] as $i => $name) {
        $p = makeProvince($this->region, $name, 'small');
        makeUser('provincial_director', $p);
        setAnnual($p, $this->kpis['core_proposals'], 2025, 10, 10 - $i);  // last year, already over
        setAnnual($p, $this->kpis['core_proposals'], 2026, 10, 5 + 3 * $i); // the year being reported: 50%, 80%, 110%
        $this->provinces[] = $p;
    }
});
afterEach(fn() => Carbon::setTestNow());

function approveYear($province, int $year = 2026, array $quarters = [1, 2, 3, 4]): void
{
    $director = User::whereRole('provincial_director')->whereProvince($province->id)->first();
    foreach ($quarters as $q) {
        KpiQuarterApproval::create(['province_id' => $province->id, 'year' => $year, 'quarter' => $q, 'approved_by' => $director->id]);
    }
}

function release(): RankingRelease
{
    return app(RankingRelease::class); // fresh instance, fresh cache
}

describe('the rule', function () {
    it('keeps a year being reported hidden', function () {
        expect(release()->status(2026))->toMatchArray([
            'released' => false, 'reason' => null, 'submitted' => 0, 'total' => 3, 'deadline' => null,
        ]);
    });

    it('shows a past year that had no deadline', function () {
        expect(release()->status(2025))->toMatchArray(['released' => true, 'reason' => 'year_ended']);
    });

    it('releases a year once every province has submitted', function () {
        foreach ($this->provinces as $p) approveYear($p);
        expect(release()->status(2026))->toMatchArray(['released' => true, 'reason' => 'all_submitted', 'submitted' => 3]);
    });

    it('counts a province as submitted only with all four quarters approved', function () {
        approveYear($this->provinces[0]);
        approveYear($this->provinces[1]);
        approveYear($this->provinces[2], 2026, [1, 2, 3]);

        expect(release()->status(2026))->toMatchArray(['released' => false, 'submitted' => 2]);
    });

    it('releases a year when its Q4 deadline passes, whoever is missing', function () {
        approveYear($this->provinces[0]);
        KpiReportingDeadline::create(['year' => 2026, 'quarter' => 4, 'deadline' => '2026-06-14']);

        expect(release()->status(2026))->toMatchArray(['released' => true, 'reason' => 'deadline_passed', 'submitted' => 1]);
    });

    it('waits through the deadline day itself', function () {
        KpiReportingDeadline::create(['year' => 2026, 'quarter' => 4, 'deadline' => '2026-06-15']);
        expect(release()->isReleased(2026))->toBeFalse();
    });

    it('waits for a future Q4 deadline even after the year ends', function () {
        KpiReportingDeadline::create(['year' => 2025, 'quarter' => 4, 'deadline' => '2026-07-31']);
        expect(release()->status(2025))->toMatchArray(['released' => false, 'deadline' => '2026-07-31']);
    });

    it('ignores other quarters\' deadlines', function () {
        KpiReportingDeadline::create(['year' => 2026, 'quarter' => 1, 'deadline' => '2026-04-30']);
        expect(release()->isReleased(2026))->toBeFalse();
    });

    it('hides the year again if an edit clears an approval before the deadline', function () {
        foreach ($this->provinces as $p) approveYear($p);
        expect(release()->isReleased(2026))->toBeTrue();

        $admin = makeUser('provincial_admin', $this->provinces[0]);
        app(KpiEntryService::class)->saveYear($this->provinces[0]->id, 2026, [], [2 => [$this->kpis['core_gross_sales']->id => 3]], $admin);

        expect(release()->isReleased(2026))->toBeFalse();
    });

    it('splits the years with data into released and pending', function () {
        expect(release()->releasedYears())->toBe([2025])
            ->and(release()->pendingYears())->toBe([2026]);
    });
});

describe('where rankings appear', function () {
    it('keeps a pending year\'s ranks and scores off the national dashboard', function () {
        $props = inertiaProps($this->actingAs(makeUser('super_admin'))->get('/dashboard')->assertOk());
        $row   = collect($props['pending_by_year'][2026])->flatten(1)->firstWhere('province', 'Abra');

        expect($props['available_years'])->toBe([2025])
            ->and($props['pending_years'])->toBe([2026])
            ->and(array_keys($props['rankings_by_year']))->toBe([2025])
            ->and($props['release'][2026]['released'])->toBeFalse()
            ->and($props['release'][2025]['reason'])->toBe('year_ended')
            ->and($row)->not->toHaveKeys(['rank', 'bucket', 'total_pct', 'subtotals_pct', 'kpi_scores', 'category_ranks', 'adjective_label'])
            ->and($row)->toHaveKeys(['province', 'director', 'submission', 'approved_quarters', 'province_url_id']);
    });

    it('reports each province\'s approved quarters for tracking', function () {
        approveYear($this->provinces[0], 2026, [1, 2]);
        $props = inertiaProps($this->actingAs(makeUser('super_admin'))->get('/dashboard'));
        $rows  = collect($props['pending_by_year'][2026])->flatten(1)->keyBy('province');

        expect($rows['Abra']['approved_quarters'])->toBe(2)
            ->and($rows['Benguet']['approved_quarters'])->toBe(0);
    });

    it('offers the current year for tracking even before any data', function () {
        \App\Models\ProvincialDirectorKPI::where('year', 2026)->delete();
        $props = inertiaProps($this->actingAs(makeUser('super_admin'))->get('/dashboard'));

        expect($props['pending_years'])->toBe([2026])
            ->and(collect($props['pending_by_year'][2026])->flatten(1))->toHaveCount(3);
    });

    it('moves the year into the rankings once released', function () {
        KpiReportingDeadline::create(['year' => 2026, 'quarter' => 4, 'deadline' => '2026-06-01']);
        $props = inertiaProps($this->actingAs(makeUser('super_admin'))->get('/dashboard'));

        expect($props['available_years'])->toBe([2026, 2025])
            ->and($props['pending_years'])->toBe([])
            ->and(collect($props['rankings_by_year'][2026])->flatten(1)->firstWhere('province', 'Kalinga')['rank'])->toBe(1);
    });

    it('applies to the regional director\'s dashboard', function () {
        $rd    = makeUser('regional_director', null, $this->region);
        $props = inertiaProps($this->actingAs($rd)->get('/dashboard'));
        expect($props['available_years'])->toBe([2025])
            ->and(collect($props['pending_by_year'][2026])->flatten(1)->first())->not->toHaveKey('rank');
    });

    it('gives the provincial dashboards the last released standing and the release status', function () {
        $director = User::whereRole('provincial_director')->whereProvince($this->provinces[0]->id)->first();
        $props    = inertiaProps($this->actingAs($director)->get('/dashboard'));

        expect($props['standing']['year'])->toBe(2025)
            ->and(array_column($props['trend'], 'year'))->toBe([2025])
            ->and($props['release'])->toMatchArray(['year' => 2026, 'released' => false]);

        $admin = makeUser('provincial_admin', $this->provinces[0]);
        $props = inertiaProps($this->actingAs($admin)->get('/dashboard'));
        expect($props['standing']['year'])->toBe(2025)
            ->and($props['release']['released'])->toBeFalse();
    });

    it('applies to the maps, the employee dashboard and the reports', function () {
        $years = fn($props) => array_map('intval', $props['available_years']);

        $rd = makeUser('regional_director', null, $this->region);

        expect($years(inertiaProps($this->actingAs(makeUser('super_admin'))->get('/performance-map'))))->toBe([2025]);
        expect($years(inertiaProps($this->actingAs($rd)->get('/regional-performance-map'))))->toBe([2025]);
        expect($years(inertiaProps($this->actingAs(makeUser('employee', $this->provinces[0]))->get('/dashboard'))))->toBe([2025]);

        $report = inertiaProps($this->actingAs($rd)->get('/regional-director-report'));
        expect(collect($report['director_rankings'])->pluck('year')->unique()->all())->toBe([2025]);
    });
});
