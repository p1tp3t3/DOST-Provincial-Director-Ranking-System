<?php

use App\Models\ProvincialDirectorKPI;
use App\Services\RankingService;

// The official ranking: per-KPI bands, weighted totals, statuses, ranking and
// Top/Average/Low buckets within each tier, and per-category standings.

beforeEach(function () {
    $this->kpis   = makeCatalog();
    $this->region = makeRegion();
    $this->year   = 2025;
});

// A province in $tier with a director and a core_proposals result of
// $accomplished out of 10 (so 10 → 100%).
function rankedProvince(string $tier, string $name, ?float $accomplished, bool $director = true)
{
    $province = makeProvince(test()->region, $name, $tier);
    if ($director) makeUser('provincial_director', $province);
    if ($accomplished !== null) setAnnual($province, test()->kpis['core_proposals'], test()->year, 10, $accomplished);
    return $province;
}

function rowFor(array $tierRows, string $province): array
{
    return collect($tierRows)->firstWhere('province', $province);
}

describe('scoring', function () {
    it('maps accomplishment % to the adjective bands', function (float $accomplished, float $score, string $label) {
        rankedProvince('small', 'P', $accomplished);
        $row = rowFor((new RankingService())->rankByYear($this->year)['small'], 'P');

        $kpiScore = collect($row['kpi_scores'])->firstWhere('code', 'core_proposals');
        expect($kpiScore['score'])->toBe($score)
            ->and($kpiScore['adjective_label'])->toBe($label)
            ->and($kpiScore['weighted_score'])->toBe(round($score * 0.40, 6));
    })->with([
        '111%+ Outstanding'     => [11.1, 1.0, 'Outstanding'],
        '100% Very Satisfactory' => [10,   0.8, 'Very Satisfactory'],
        '76% Satisfactory'      => [7.6,  0.6, 'Satisfactory'],
        '26% Average'           => [2.6,  0.4, 'Average'],
        '11% Unsatisfactory'    => [1.1,  0.2, 'Unsatisfactory'],
        'below 11% Poor'        => [1,    0.0, 'Poor'],
    ]);

    it('adds weighted scores into category subtotals and the total', function () {
        $p = rankedProvince('small', 'P', 12);                                   // 120% → 1.0 × 0.40
        setAnnual($p, $this->kpis['func_linkages_established'], $this->year, 4, 4); // 100% → 0.8 × 0.30
        $row = rowFor((new RankingService())->rankByYear($this->year)['small'], 'P');

        expect($row['subtotals']['CORE'])->toBe(0.4)
            ->and($row['subtotals']['STRATEGIC'])->toBe(0.24)
            ->and($row['subtotals']['SUPPORT'])->toBe(0.0)
            ->and($row['total_pct'])->toBe(64.0)
            ->and($row['subtotals_pct']['STRATEGIC'])->toBe(24.0);
    });

    it('scores % Delinquent SETUP from its two inputs, inverted', function (int $delinquent, float $score) {
        $p = rankedProvince('small', 'P', null);
        setAnnual($p, $this->kpis['input_setup_ongoing_count'], $this->year, null, 10);
        setAnnual($p, $this->kpis['input_setup_delinquent_count'], $this->year, null, $delinquent);

        $row      = rowFor((new RankingService())->rankByYear($this->year)['small'], 'P');
        $kpiScore = collect($row['kpi_scores'])->firstWhere('code', 'core_setup_delinquent_pct');
        expect($kpiScore['score'])->toBe($score)
            ->and($kpiScore['actual_pct'])->toBe(round($delinquent / 10 * 100, 2));
    })->with([
        'none delinquent'  => [0, 1.0],
        '20% delinquent'   => [2, 0.8],
        '30% delinquent'   => [3, 0.6],
        '60% delinquent'   => [6, 0.4],
        '90% delinquent'   => [9, 0.2],
        'all delinquent'   => [10, 0.0],
    ]);

    it('gives no score to the delinquency KPI without ongoing projects', function () {
        $p = rankedProvince('small', 'P', null);
        setAnnual($p, $this->kpis['input_setup_delinquent_count'], $this->year, null, 3);

        $row = rowFor((new RankingService())->rankByYear($this->year)['small'], 'P');
        expect(collect($row['kpi_scores'])->firstWhere('code', 'core_setup_delinquent_pct')['adjective_label'])->toBe('No data');
    });

    it('scores a missing target against the median of peer targets', function () {
        $peers = [['A', 10], ['B', 20], ['C', 30]];
        foreach ($peers as [$name, $target]) {
            $p = makeProvince($this->region, $name, 'small');
            makeUser('provincial_director', $p);
            setAnnual($p, $this->kpis['core_proposals'], $this->year, $target, 1);
        }
        $p = rankedProvince('small', 'NoTarget', null);
        setAnnual($p, $this->kpis['core_proposals'], $this->year, null, 20); // 20 / median 20 = 100%

        $row = rowFor((new RankingService())->rankByYear($this->year)['small'], 'NoTarget');
        expect(collect($row['kpi_scores'])->firstWhere('code', 'core_proposals')['actual_pct'])->toBe(100.0);
    });

    it('does not score an unscored input KPI', function () {
        $p = rankedProvince('small', 'P', null);
        setAnnual($p, $this->kpis['input_setup_ongoing_count'], $this->year, 1, 100);

        $row = rowFor((new RankingService())->rankByYear($this->year)['small'], 'P');
        expect(collect($row['kpi_scores'])->pluck('code'))->not->toContain('input_setup_ongoing_count')
            ->and($row['total'])->toBe(0.0);
    });
});

describe('status and ranking', function () {
    it('separates ranked, no-data and no-director provinces', function () {
        rankedProvince('small', 'Ranked', 10);
        rankedProvince('small', 'NoData', null);
        rankedProvince('small', 'Vacant', 10, director: false);

        $rows = (new RankingService())->rankByYear($this->year)['small'];
        expect(rowFor($rows, 'Ranked')['status'])->toBe('ranked')
            ->and(rowFor($rows, 'Ranked')['rank'])->toBe(1)
            ->and(rowFor($rows, 'NoData')['status'])->toBe('no_data')
            ->and(rowFor($rows, 'NoData')['rank'])->toBeNull()
            ->and(rowFor($rows, 'Vacant')['status'])->toBe('no_director')
            ->and(rowFor($rows, 'Vacant')['rank'])->toBeNull()
            // tier_size counts ranked provinces only
            ->and(rowFor($rows, 'NoData')['tier_size'])->toBe(1)
            // unranked rows come after ranked ones, alphabetically
            ->and(array_column($rows, 'province'))->toBe(['Ranked', 'NoData', 'Vacant']);
    });

    it('ranks each tier on its own', function () {
        rankedProvince('small', 'SmallLow', 5);
        rankedProvince('small', 'SmallHigh', 12);
        rankedProvince('large', 'LargeOnly', 1);

        $ranking = (new RankingService())->rankByYear($this->year);
        expect(array_column($ranking['small'], 'province'))->toBe(['SmallHigh', 'SmallLow'])
            ->and(rowFor($ranking['large'], 'LargeOnly')['rank'])->toBe(1)
            ->and(rowFor($ranking['large'], 'LargeOnly')['tier_size'])->toBe(1);
    });

    it('buckets the top 20% / bottom 10% once a tier has five ranked provinces', function () {
        foreach ([12, 10, 8, 5, 2] as $i => $acc) rankedProvince('small', "P{$i}", $acc);

        $rows = (new RankingService())->rankByYear($this->year)['small'];
        expect(array_column($rows, 'bucket'))->toBe(['Top', 'Average', 'Average', 'Average', 'Low']);
    });

    it('leaves buckets empty below five ranked provinces', function () {
        foreach ([12, 10, 8, 5] as $i => $acc) rankedProvince('small', "P{$i}", $acc);

        $rows = (new RankingService())->rankByYear($this->year)['small'];
        expect(array_unique(array_column($rows, 'bucket')))->toBe([null])
            ->and(array_column($rows, 'rank'))->toBe([1, 2, 3, 4]);
    });

    it('breaks a tied total on category subtotals, then on name', function () {
        // Same total (40%): one from CORE, the other from STRATEGIC + SUPPORT.
        rankedProvince('small', 'Zeta', 12);                                   // CORE 0.40
        $mix  = rankedProvince('small', 'Alpha', null);
        setAnnual($mix, $this->kpis['func_linkages_established'], $this->year, 1, 2); // 0.30
        setAnnual($mix, $this->kpis['supp_facebook_posts'], $this->year, 1, 2);       // 0.05
        setAnnual($mix, $this->kpis['supp_customer_satisfaction'], $this->year, 1, 2); // 0.05
        rankedProvince('small', 'Beta', 12);                                    // same as Zeta

        $rows = (new RankingService())->rankByYear($this->year)['small'];
        // Heavier CORE subtotal wins; Beta and Zeta are identical, so by name.
        expect(array_column($rows, 'province'))->toBe(['Beta', 'Zeta', 'Alpha']);
    });

    it('records each province\'s official standing on every category', function () {
        rankedProvince('small', 'CoreStrong', 12);
        $b = rankedProvince('small', 'StrategicStrong', 1);
        setAnnual($b, $this->kpis['func_linkages_established'], $this->year, 1, 2);

        $rows = (new RankingService())->rankByYear($this->year)['small'];
        expect(rowFor($rows, 'CoreStrong')['category_ranks']['CORE']['rank'])->toBe(1)
            ->and(rowFor($rows, 'CoreStrong')['category_ranks']['STRATEGIC']['rank'])->toBe(2)
            ->and(rowFor($rows, 'StrategicStrong')['category_ranks']['STRATEGIC']['rank'])->toBe(1);
    });

    it('gives every category a complete 1..n ranking', function () {
        foreach ([12, 10, 8, 5, 2, 1] as $i => $acc) rankedProvince('small', "P{$i}", $acc);

        $rows = (new RankingService())->rankByYear($this->year)['small'];
        foreach (['CORE', 'STRATEGIC', 'SUPPORT'] as $code) {
            $ranks = collect($rows)->pluck("category_ranks.{$code}.rank")->sort()->values()->all();
            expect($ranks)->toBe([1, 2, 3, 4, 5, 6]);
        }
    });

    it('gives unranked provinces no category ranks', function () {
        rankedProvince('small', 'NoData', null);
        $row = rowFor((new RankingService())->rankByYear($this->year)['small'], 'NoData');
        expect((array) $row['category_ranks'])->toBe([]);
    });
});

describe('official standings for a subset of provinces', function () {
    it('keeps the national rank, bucket and tier size', function () {
        $other  = makeRegion('Region II');
        foreach ([12, 10, 8, 5, 2] as $i => $acc) {
            $p = makeProvince($i < 2 ? $this->region : $other, "P{$i}", 'small');
            makeUser('provincial_director', $p);
            setAnnual($p, $this->kpis['core_proposals'], $this->year, 10, $acc);
        }
        $regionIds = $this->region->provinces()->pluck('id')->all();
        $svc       = new RankingService();

        $national = collect($svc->rankByYear($this->year)['small'])->keyBy('province_id');
        $official = $svc->officialRankingsFor($this->year, $regionIds)['small'];

        expect($official)->toHaveCount(2);
        foreach ($official as $row) {
            $n = $national[$row['province_id']];
            expect([$row['rank'], $row['bucket'], $row['tier_size']])->toBe([$n['rank'], $n['bucket'], 5]);
        }
    });

    it('differs from rankByYear with a subset, which re-ranks among the subset', function () {
        foreach ([12, 10, 8] as $i => $acc) {
            $p = makeProvince($i === 2 ? $this->region : makeRegion("R{$i}"), "P{$i}", 'small');
            makeUser('provincial_director', $p);
            setAnnual($p, $this->kpis['core_proposals'], $this->year, 10, $acc);
        }
        $ids = $this->region->provinces()->pluck('id')->all();
        $svc = new RankingService();

        expect($svc->officialRankingsFor($this->year, $ids)['small'][0]['rank'])->toBe(3)
            ->and($svc->rankByYear($this->year, $ids)['small'][0]['rank'])->toBe(1);
    });
});

describe('data ownership', function () {
    it('lists years with data, newest first', function () {
        $p = rankedProvince('small', 'P', 10);
        setAnnual($p, $this->kpis['core_gross_sales'], 2023, 1, 1);

        expect((new RankingService())->availableYears())->toEqual([2025, 2023]);
    });

    it('keeps counting a province\'s figures after its director is gone', function () {
        $p        = makeProvince($this->region, 'P', 'small');
        $director = makeUser('provincial_director', $p);
        setAnnual($p, $this->kpis['core_proposals'], $this->year, 10, 12)
            ->update(['provincial_director_id' => $director->id]);

        $director->provinces()->detach(); // the post is vacated

        $row = rowFor((new RankingService())->rankByYear($this->year)['small'], 'P');
        expect($row['total_pct'])->toBe(40.0)                // the figures still score...
            ->and($row['status'])->toBe('no_director');     // ...though the post is vacant
    });
});
