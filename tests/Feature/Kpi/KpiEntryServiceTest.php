<?php

use App\Models\FacebookPost;
use App\Models\KpiCorrectionRequest;
use App\Models\KpiQuarterApproval;
use App\Models\KpiQuarterValue;
use App\Models\KpiReportingDeadline;
use App\Models\Linkage;
use App\Models\ProvincialDirectorKPI;
use App\Services\KpiEntryService;
use Illuminate\Validation\ValidationException;

// Saving quarterly figures, rolling them up into the ranked annual figure,
// and the deadline / correction locks.

beforeEach(function () {
    $this->kpis     = makeCatalog();
    $this->province = makeProvince(null, 'Pangasinan', 'large');
    $this->director = makeUser('provincial_director', $this->province);
    $this->admin    = makeUser('provincial_admin', $this->province);
    $this->svc      = app(KpiEntryService::class);
    $this->year     = 2026;
});

function annualRow(string $code, ?int $year = null): ?ProvincialDirectorKPI
{
    return ProvincialDirectorKPI::where([
        'province_id' => test()->province->id,
        'kpi_id'      => test()->kpis[$code]->id,
        'year'        => $year ?? test()->year,
    ])->first();
}

function deadline(int $quarter, string $date, ?int $year = null): void
{
    KpiReportingDeadline::create(['year' => $year ?? test()->year, 'quarter' => $quarter, 'deadline' => $date]);
}

function correction(?int $quarter, string $status = KpiCorrectionRequest::APPROVED, $openUntil = null): KpiCorrectionRequest
{
    return KpiCorrectionRequest::create([
        'province_id'  => test()->province->id,
        'year'         => test()->year,
        'quarter'      => $quarter,
        'reason'       => 'test',
        'requested_by' => test()->admin->id,
        'status'       => $status,
        'open_until'   => $openUntil ?? now()->addDays(7),
    ]);
}

function save(array $targets = [], array $quarters = []): void
{
    $ids = fn(array $byCode) => collect($byCode)->mapWithKeys(fn($v, $code) => [test()->kpis[$code]->id => $v])->all();
    test()->svc->saveYear(
        test()->province->id, test()->year,
        $ids($targets),
        collect($quarters)->map($ids)->all(),
        test()->admin,
    );
}

describe('roll-up', function () {
    it('adds quarters for sum KPIs', function () {
        save(quarters: [1 => ['core_proposals' => 10], 2 => ['core_proposals' => 20]]);
        expect(annualRow('core_proposals')->accomplished)->toBe('30');
    });

    it('takes the latest quarter for running totals', function () {
        save(quarters: [1 => ['input_setup_ongoing_count' => 5], 3 => ['input_setup_ongoing_count' => 7]]);
        expect(annualRow('input_setup_ongoing_count')->accomplished)->toBe('7');
    });

    it('averages rating KPIs', function () {
        save(quarters: [1 => ['supp_customer_satisfaction' => 4], 2 => ['supp_customer_satisfaction' => 4.5]]);
        expect(annualRow('supp_customer_satisfaction')->accomplished)->toBe('4.25');
    });

    it('re-rolls when a quarter changes', function () {
        save(quarters: [1 => ['core_proposals' => 10], 2 => ['core_proposals' => 20]]);
        save(quarters: [2 => ['core_proposals' => 5]]);
        expect(annualRow('core_proposals')->accomplished)->toBe('15');
    });

    it('clears the annual figure when every quarter is cleared', function () {
        save(quarters: [1 => ['core_proposals' => 10]]);
        save(quarters: [1 => ['core_proposals' => null]]);
        expect(annualRow('core_proposals'))->toBeNull();
    });

    it('keeps the target when the quarters are cleared', function () {
        save(targets: ['core_proposals' => 40], quarters: [1 => ['core_proposals' => 10]]);
        save(quarters: [1 => ['core_proposals' => null]]);
        expect(annualRow('core_proposals')->target)->toBe('40')
            ->and(annualRow('core_proposals')->accomplished)->toBeNull();
    });

    it('leaves a year reported as one annual figure alone until quarters are entered', function () {
        setAnnual($this->province, $this->kpis['core_proposals'], 2025, 50, 45);
        $this->svc->rollUp($this->province->id, $this->kpis['core_proposals']->id, 2025, null, null);
        expect(annualRow('core_proposals', 2025)->accomplished)->toBe('45');
    });

    it('replaces an annual figure once quarters are entered for that year', function () {
        setAnnual($this->province, $this->kpis['core_proposals'], $this->year, 50, 45);
        save(quarters: [1 => ['core_proposals' => 8]]);
        expect(annualRow('core_proposals')->accomplished)->toBe('8')
            ->and(annualRow('core_proposals')->target)->toBe('50');
    });

    it('re-rolls every province when a KPI\'s rule changes', function () {
        save(quarters: [1 => ['core_gross_sales' => 10], 2 => ['core_gross_sales' => 4]]);
        $this->kpis['core_gross_sales']->update(['rollup_rule' => 'latest']);

        $this->svc->rollUpEverywhere($this->kpis['core_gross_sales']->id, $this->admin->id);
        expect(annualRow('core_gross_sales')->accomplished)->toBe('4');
    });
});

describe('saving', function () {
    it('saves targets as numbers', function () {
        save(targets: ['core_proposals' => '12.50']);
        expect(annualRow('core_proposals')->target)->toBe('12.5');
    });

    it('keeps a legacy breakdown target that is numerically unchanged', function () {
        setAnnual($this->province, $this->kpis['core_proposals'], $this->year, "1 - extension\n1 - public good", null);
        save(targets: ['core_proposals' => 2]);
        expect(annualRow('core_proposals')->target)->toBe("1 - extension\n1 - public good");
    });

    it('records who changed a figure and the director in office', function () {
        save(targets: ['core_proposals' => 10], quarters: [1 => ['core_proposals' => 3]]);
        $row = annualRow('core_proposals');
        $q   = KpiQuarterValue::first();

        expect($row->updated_by)->toBe($this->admin->id)
            ->and($row->provincial_director_id)->toBe($this->director->id)
            ->and($row->updated_at)->not->toBeNull()
            ->and($q->updated_by)->toBe($this->admin->id)
            ->and($q->provincial_director_id)->toBe($this->director->id);
    });

    it('ignores the derived KPI', function () {
        save(targets: ['core_setup_delinquent_pct' => 5], quarters: [1 => ['core_setup_delinquent_pct' => 3]]);
        expect(annualRow('core_setup_delinquent_pct'))->toBeNull()
            ->and(KpiQuarterValue::count())->toBe(0);
    });

    it('takes targets but not typed quarters for record-backed KPIs', function () {
        save(targets: ['func_linkages_established' => 6], quarters: [1 => ['func_linkages_established' => 99]]);
        expect(annualRow('func_linkages_established')->target)->toBe('6')
            ->and(annualRow('func_linkages_established')->accomplished)->toBeNull()
            ->and(KpiQuarterValue::count())->toBe(0);
    });

    it('does not create rows for empty quarter inputs', function () {
        save(quarters: [1 => ['core_proposals' => null, 'core_gross_sales' => '']]);
        expect(KpiQuarterValue::count())->toBe(0)
            ->and(ProvincialDirectorKPI::count())->toBe(0);
    });

    it('stores figures against the province, not the director', function () {
        save(quarters: [1 => ['core_proposals' => 10]]);
        $this->director->provinces()->detach();
        $successor = makeUser('provincial_director', $this->province);

        save(quarters: [2 => ['core_proposals' => 5]]);
        expect(annualRow('core_proposals')->accomplished)->toBe('15')
            ->and(annualRow('core_proposals')->provincial_director_id)->toBe($successor->id);
    });
});

describe('director approval', function () {
    beforeEach(function () {
        save(quarters: [1 => ['core_proposals' => 10], 2 => ['core_proposals' => 10]]);
        KpiQuarterApproval::create(['province_id' => $this->province->id, 'year' => $this->year, 'quarter' => 1, 'approved_by' => $this->director->id]);
        KpiQuarterApproval::create(['province_id' => $this->province->id, 'year' => $this->year, 'quarter' => 2, 'approved_by' => $this->director->id]);
    });

    it('survives a save that changes nothing', function () {
        save(quarters: [1 => ['core_proposals' => 10]]);
        expect(KpiQuarterApproval::where('quarter', 1)->exists())->toBeTrue();
    });

    it('is cleared when its quarter changes', function () {
        save(quarters: [1 => ['core_proposals' => 11]]);
        expect(KpiQuarterApproval::where('quarter', 1)->exists())->toBeFalse();
    });

    it('is kept for quarters that did not change', function () {
        save(quarters: [1 => ['core_proposals' => 11]]);
        expect(KpiQuarterApproval::where('quarter', 2)->exists())->toBeTrue();
    });

    it('is not cleared by a target change', function () {
        save(targets: ['core_proposals' => 99]);
        expect(KpiQuarterApproval::count())->toBe(2);
    });
});

describe('deadlines and corrections', function () {
    it('keeps a quarter open with no deadline', function () {
        expect($this->svc->isQuarterOpen($this->province->id, $this->year, 1))->toBeTrue()
            ->and($this->svc->areTargetsOpen($this->province->id, $this->year))->toBeTrue();
    });

    it('keeps a quarter open through its deadline day', function () {
        deadline(1, now()->toDateString());
        expect($this->svc->isQuarterOpen($this->province->id, $this->year, 1))->toBeTrue();
    });

    it('locks a quarter after its deadline', function () {
        deadline(1, now()->subDay()->toDateString());
        expect($this->svc->isQuarterOpen($this->province->id, $this->year, 1))->toBeFalse()
            ->and($this->svc->isQuarterOpen($this->province->id, $this->year, 2))->toBeTrue();
    });

    it('only applies a deadline to its own year', function () {
        deadline(1, now()->subDay()->toDateString(), $this->year - 1);
        expect($this->svc->isQuarterOpen($this->province->id, $this->year, 1))->toBeTrue();
    });

    it('rejects saving a locked quarter, and saves nothing', function () {
        deadline(1, now()->subDay()->toDateString());
        expect(fn() => save(quarters: [1 => ['core_proposals' => 5], 2 => ['core_proposals' => 5]]))
            ->toThrow(ValidationException::class, 'Q1 2026 is locked');
        expect(KpiQuarterValue::count())->toBe(0);
    });

    it('reopens a locked quarter for an approved correction', function () {
        deadline(1, now()->subDay()->toDateString());
        correction(1);
        expect($this->svc->isQuarterOpen($this->province->id, $this->year, 1))->toBeTrue();
        save(quarters: [1 => ['core_proposals' => 5]]);
        expect(annualRow('core_proposals')->accomplished)->toBe('5');
    });

    it('does not reopen for a correction that is not approved or has expired', function ($status, $openUntil) {
        deadline(1, now()->subDay()->toDateString());
        correction(1, $status, $openUntil);
        expect($this->svc->isQuarterOpen($this->province->id, $this->year, 1))->toBeFalse();
    })->with([
        'pending at region'  => [KpiCorrectionRequest::PENDING_REGIONAL, null],
        'pending at central' => [KpiCorrectionRequest::PENDING_CENTRAL, null],
        'declined'           => [KpiCorrectionRequest::DECLINED, null],
        'returned'           => [KpiCorrectionRequest::RETURNED, null],
        'approved, expired'  => [KpiCorrectionRequest::APPROVED, now()->subMinute()],
    ]);

    it('does not reopen another quarter or another province', function () {
        deadline(1, now()->subDay()->toDateString());
        deadline(2, now()->subDay()->toDateString());
        correction(2);
        $other = makeProvince(null, 'Other');

        expect($this->svc->isQuarterOpen($this->province->id, $this->year, 1))->toBeFalse()
            ->and($this->svc->isQuarterOpen($other->id, $this->year, 2))->toBeFalse();
    });

    it('locks targets only with the Q4 deadline', function () {
        deadline(1, now()->subDay()->toDateString());
        expect($this->svc->areTargetsOpen($this->province->id, $this->year))->toBeTrue();

        deadline(4, now()->subDay()->toDateString());
        expect($this->svc->areTargetsOpen($this->province->id, $this->year))->toBeFalse();
        expect(fn() => save(targets: ['core_proposals' => 1]))->toThrow(ValidationException::class, 'targets are locked');
    });

    it('reopens targets only for a targets correction', function () {
        deadline(4, now()->subDay()->toDateString());
        correction(4);
        expect($this->svc->areTargetsOpen($this->province->id, $this->year))->toBeFalse();

        correction(null);
        expect($this->svc->areTargetsOpen($this->province->id, $this->year))->toBeTrue();
    });

    it('stamps an open correction when its period changes, by typing or through a record', function () {
        deadline(1, now()->subDay()->toDateString());
        deadline(4, now()->subDay()->toDateString());   // also releases the year's rankings
        $q1      = correction(1);
        $q4      = correction(4);
        $targets = correction(null);

        save(quarters: [1 => ['core_proposals' => 5]]);
        expect($q1->fresh()->applied_at)->not->toBeNull()
            ->and($q1->fresh()->revised_at)->not->toBeNull()
            ->and($q4->fresh()->applied_at)->toBeNull()
            ->and($targets->fresh()->applied_at)->toBeNull();

        // Saving the same figure again changes nothing, so it isn't stamped again.
        $q1->update(['applied_at' => null, 'revised_at' => null]);
        save(quarters: [1 => ['core_proposals' => 5]]);
        expect($q1->fresh()->applied_at)->toBeNull();

        save(targets: ['core_proposals' => 12]);
        expect($targets->fresh()->applied_at)->not->toBeNull();

        FacebookPost::create(['province_id' => $this->province->id, 'provincial_director_id' => $this->director->id, 'year' => $this->year,
            'title' => 'Post', 'post_type' => 'Text', 'date_posted' => "{$this->year}-11-05"]);
        $this->svc->syncRecordKpi('supp_facebook_posts', $this->province->id, $this->year, null);
        expect($q4->fresh()->applied_at)->not->toBeNull();
    });
});

describe('record-backed KPIs', function () {
    function linkage(string $date, ?int $year = null): Linkage
    {
        return Linkage::create([
            'province_id' => test()->province->id, 'provincial_director_id' => test()->director->id,
            'year' => $year ?? test()->year, 'partner_organization' => 'Partner', 'title' => 'MOA',
            'type' => 'MOA', 'date_signed' => $date,
        ]);
    }

    it('counts records into the quarter of their date', function () {
        linkage('2026-02-10'); linkage('2026-03-31'); linkage('2026-08-01');
        $this->svc->syncRecordKpi('func_linkages_established', $this->province->id, $this->year, $this->admin->id);

        $quarters = KpiQuarterValue::where('kpi_id', $this->kpis['func_linkages_established']->id)
            ->orderBy('quarter')->pluck('accomplished')->all();
        expect($quarters)->toBe([2.0, 0.0, 1.0, 0.0])
            ->and(annualRow('func_linkages_established')->accomplished)->toBe('3');
    });

    it('clears the figure when the last record is removed', function () {
        $l = linkage('2026-02-10');
        $this->svc->syncRecordKpi('func_linkages_established', $this->province->id, $this->year, null);
        $l->delete();
        $this->svc->syncRecordKpi('func_linkages_established', $this->province->id, $this->year, null);

        expect(annualRow('func_linkages_established'))->toBeNull();
    });

    it('counts Facebook posts the same way', function () {
        FacebookPost::create(['province_id' => $this->province->id, 'provincial_director_id' => $this->director->id, 'year' => $this->year,
            'title' => 'Post', 'post_type' => 'Text', 'date_posted' => '2026-11-05']);
        $this->svc->syncRecordKpi('supp_facebook_posts', $this->province->id, $this->year, null);

        expect(annualRow('supp_facebook_posts')->accomplished)->toBe('1')
            ->and(KpiQuarterValue::where('quarter', 4)->value('accomplished'))->toBe(1.0);
    });

    it('rejects record changes dated in a locked quarter', function () {
        deadline(1, now()->subDay()->toDateString());
        expect(fn() => $this->svc->assertRecordQuarterOpen($this->province->id, $this->year, '2026-02-01'))
            ->toThrow(ValidationException::class, 'Q1 2026 is locked');
        $this->svc->assertRecordQuarterOpen($this->province->id, $this->year, '2026-05-01'); // Q2 is open
    });
});
