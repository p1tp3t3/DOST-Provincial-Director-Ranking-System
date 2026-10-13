<?php

use App\Models\FacebookPost;
use App\Models\KpiQuarterValue;
use App\Models\KpiReportingDeadline;
use App\Models\Linkage;
use App\Models\ProvincialDirectorKPI;

// Linkages and Facebook posts: the records behind two KPIs. They belong to
// the province, their dates decide the quarter they count in, and records in
// a locked quarter can't change.

beforeEach(function () {
    $this->kpis     = makeCatalog();
    $this->province = makeProvince(null, 'Pangasinan');
    $this->director = makeUser('provincial_director', $this->province);
    $this->admin    = makeUser('provincial_admin', $this->province);
    $this->base     = "/linkages/{$this->director->id}/2026";
});

function linkageData(array $overrides = []): array
{
    return ['partner_organization' => 'DTI', 'title' => 'Tech transfer', 'type' => 'MOA', 'date_signed' => '2026-02-10', ...$overrides];
}

function linkageAnnual(): ?string
{
    return ProvincialDirectorKPI::where(['province_id' => test()->province->id, 'kpi_id' => test()->kpis['func_linkages_established']->id, 'year' => 2026])
        ->value('accomplished');
}

function linkageQuarters(): array
{
    return KpiQuarterValue::where('kpi_id', test()->kpis['func_linkages_established']->id)->orderBy('quarter')->pluck('accomplished')->all();
}

describe('linkages', function () {
    it('stores a record under the province and counts it in its quarter', function () {
        $this->actingAs($this->admin)->post($this->base, linkageData())->assertSessionHasNoErrors();

        expect(Linkage::first()->province_id)->toBe($this->province->id)
            ->and(linkageQuarters())->toBe([1.0, 0.0, 0.0, 0.0])
            ->and(linkageAnnual())->toBe('1');
    });

    it('moves the count when a record\'s date changes quarter', function () {
        $this->actingAs($this->admin)->post($this->base, linkageData());
        $this->actingAs($this->admin)->put("{$this->base}/" . Linkage::first()->id, linkageData(['date_signed' => '2026-10-01']));

        expect(linkageQuarters())->toBe([0.0, 0.0, 0.0, 1.0])
            ->and(linkageAnnual())->toBe('1');
    });

    it('drops the count when a record is removed', function () {
        $this->actingAs($this->admin)->post($this->base, linkageData());
        $this->actingAs($this->admin)->post($this->base, linkageData(['date_signed' => '2026-05-01']));
        $this->actingAs($this->admin)->delete("{$this->base}/" . Linkage::first()->id);

        expect(Linkage::count())->toBe(1)
            ->and(linkageAnnual())->toBe('1');
    });

    it('lists the province\'s records whoever was director', function () {
        Linkage::create(['province_id' => $this->province->id, 'provincial_director_id' => null, 'year' => 2026, ...linkageData(['title' => 'From the previous director'])]);

        $this->actingAs($this->admin)->get($this->base)->assertOk()
            ->assertInertia(fn($page) => $page->component('Modules/Linkages/Index')
                ->where('count', 1)
                ->where('linkages.0.title', 'From the previous director')
                ->where('return_url', '/provincial-kpi/2026'));
    });

    it('refuses records dated in a locked quarter', function () {
        KpiReportingDeadline::create(['year' => 2026, 'quarter' => 1, 'deadline' => now()->subDay()->toDateString()]);

        $this->actingAs($this->admin)->post($this->base, linkageData())->assertSessionHasErrors('date');
        expect(Linkage::count())->toBe(0);
    });

    it('refuses moving or removing a record out of a locked quarter', function () {
        $this->actingAs($this->admin)->post($this->base, linkageData());
        $id = Linkage::first()->id;
        KpiReportingDeadline::create(['year' => 2026, 'quarter' => 1, 'deadline' => now()->subDay()->toDateString()]);

        $this->actingAs($this->admin)->put("{$this->base}/{$id}", linkageData(['date_signed' => '2026-06-01']))->assertSessionHasErrors('date');
        $this->actingAs($this->admin)->delete("{$this->base}/{$id}")->assertSessionHasErrors('date');

        expect(Linkage::first()->date_signed->toDateString())->toBe('2026-02-10');
    });

    it('refuses moving a record into a locked quarter', function () {
        $this->actingAs($this->admin)->post($this->base, linkageData(['date_signed' => '2026-05-01']));
        KpiReportingDeadline::create(['year' => 2026, 'quarter' => 1, 'deadline' => now()->subDay()->toDateString()]);

        $this->actingAs($this->admin)->put("{$this->base}/" . Linkage::first()->id, linkageData(['date_signed' => '2026-02-01']))
            ->assertSessionHasErrors('date');
    });

    it('keeps other provinces out', function () {
        $otherAdmin = makeUser('provincial_admin', makeProvince(null, 'Other'));
        $this->actingAs($otherAdmin)->get($this->base)->assertForbidden();
        $this->actingAs($otherAdmin)->post($this->base, linkageData())->assertForbidden();
    });

    it('keeps the director and other roles out', function () {
        $this->actingAs($this->director)->get($this->base)->assertForbidden();
        $this->actingAs(makeUser('employee', $this->province))->get($this->base)->assertForbidden();
    });

    it('validates the record', function () {
        $this->actingAs($this->admin)->post($this->base, linkageData(['type' => 'CONTRACT', 'date_signed' => '']))
            ->assertSessionHasErrors(['type', 'date_signed']);
    });
});

describe('facebook posts', function () {
    beforeEach(function () {
        $this->fbBase = "/facebook-posts/{$this->director->id}/2026";
    });

    it('stores, moves and removes posts with their quarterly counts', function () {
        $post = ['title' => 'Event', 'post_type' => 'Event', 'date_posted' => '2026-03-01'];
        $this->actingAs($this->admin)->post($this->fbBase, $post)->assertSessionHasNoErrors();
        $id = FacebookPost::first()->id;

        expect(FacebookPost::first()->province_id)->toBe($this->province->id);

        $this->actingAs($this->admin)->put("{$this->fbBase}/{$id}", [...$post, 'date_posted' => '2026-09-01']);
        $counts = KpiQuarterValue::where('kpi_id', $this->kpis['supp_facebook_posts']->id)->orderBy('quarter')->pluck('accomplished')->all();
        expect($counts)->toBe([0.0, 0.0, 1.0, 0.0]);

        $this->actingAs($this->admin)->delete("{$this->fbBase}/{$id}");
        expect(ProvincialDirectorKPI::where('kpi_id', $this->kpis['supp_facebook_posts']->id)->exists())->toBeFalse();
    });

    it('refuses posts dated in a locked quarter', function () {
        KpiReportingDeadline::create(['year' => 2026, 'quarter' => 3, 'deadline' => now()->subDay()->toDateString()]);
        $this->actingAs($this->admin)->post($this->fbBase, ['title' => 'x', 'post_type' => 'Text', 'date_posted' => '2026-08-01'])
            ->assertSessionHasErrors('date');
    });
});
