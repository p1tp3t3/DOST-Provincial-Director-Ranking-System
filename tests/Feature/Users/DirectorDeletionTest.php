<?php

use App\Models\FacebookPost;
use App\Models\KpiQuarterValue;
use App\Models\Linkage;
use App\Models\ProvincialDirectorKPI;
use App\Models\User;
use App\Services\KpiEntryService;

// KPI figures and evidence belong to the province: deleting the director's
// account vacates the post but keeps the province's data.

it('keeps the province\'s KPI data when its director\'s account is deleted', function () {
    $kpis     = makeCatalog();
    $province = makeProvince();
    $director = makeUser('provincial_director', $province);
    $admin    = makeUser('provincial_admin', $province);

    app(KpiEntryService::class)->saveYear($province->id, 2026, [$kpis['core_proposals']->id => 10], [1 => [$kpis['core_proposals']->id => 4]], $admin);
    Linkage::create(['province_id' => $province->id, 'provincial_director_id' => $director->id, 'year' => 2026,
        'partner_organization' => 'DTI', 'title' => 'MOA', 'type' => 'MOA', 'date_signed' => '2026-01-10']);
    FacebookPost::create(['province_id' => $province->id, 'provincial_director_id' => $director->id, 'year' => 2026,
        'title' => 'Post', 'post_type' => 'Text', 'date_posted' => '2026-01-10']);

    $this->actingAs($admin)->delete("/users/{$director->id}")->assertRedirect();

    expect(User::find($director->id))->toBeNull()
        ->and(ProvincialDirectorKPI::where('province_id', $province->id)->value('accomplished'))->toBe('4')
        ->and(ProvincialDirectorKPI::first()->provincial_director_id)->toBeNull()
        ->and(KpiQuarterValue::first()->provincial_director_id)->toBeNull()
        ->and(Linkage::first()->provincial_director_id)->toBeNull()
        ->and(FacebookPost::count())->toBe(1);
});

it('keeps a provincial admin from deleting users in another province', function () {
    $admin  = makeUser('provincial_admin', makeProvince());
    $target = makeUser('employee', makeProvince());

    $this->actingAs($admin)->delete("/users/{$target->id}")->assertForbidden();
    expect(User::find($target->id))->not->toBeNull();
});
