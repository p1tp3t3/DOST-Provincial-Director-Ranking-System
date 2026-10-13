<?php

use App\Models\User;
use Illuminate\Support\Facades\Crypt;

// A regional director sees only their region: provinces, profiles, users and
// reports — with official (national) ranks.

beforeEach(function () {
    $this->kpis   = makeCatalog();
    $this->region = makeRegion('Region I');
    $this->mine   = makeProvince($this->region, 'Pangasinan', 'large');
    $this->theirs = makeProvince(makeRegion('Region II'), 'Cagayan', 'large');

    $this->myDirector    = makeUser('provincial_director', $this->mine);
    $this->theirDirector = makeUser('provincial_director', $this->theirs);
    $this->rd            = makeUser('regional_director', null, $this->region);

    setAnnual($this->mine, $this->kpis['core_proposals'], 2025, 10, 5);
    setAnnual($this->theirs, $this->kpis['core_proposals'], 2025, 10, 10);
});

describe('profiles', function () {
    it('lets a regional director open profiles in their region', function () {
        $this->actingAs($this->rd)->get("/profile/{$this->myDirector->id}")->assertOk();
        $this->actingAs($this->rd)->get('/profile/' . makeUser('employee', $this->mine)->id)->assertOk();
    });

    it('keeps a regional director out of profiles in other regions', function () {
        $this->actingAs($this->rd)->get("/profile/{$this->theirDirector->id}")->assertForbidden();
    });

    it('keeps the other roles\' rules', function () {
        $admin    = makeUser('provincial_admin', $this->mine);
        $employee = makeUser('employee', $this->mine);

        $this->actingAs(makeUser('super_admin'))->get("/profile/{$this->theirDirector->id}")->assertOk();
        $this->actingAs($admin)->get("/profile/{$this->myDirector->id}")->assertOk();
        $this->actingAs($admin)->get("/profile/{$this->theirDirector->id}")->assertForbidden();
        $this->actingAs($employee)->get("/profile/{$this->myDirector->id}")->assertOk();
        $this->actingAs($employee)->get("/profile/{$admin->id}")->assertForbidden();
    });
});

describe('scoped pages', function () {
    it('opens only provinces in the region', function () {
        $this->actingAs($this->rd)->get('/province-directories/' . Crypt::encrypt($this->mine->id))->assertOk();
        $this->actingAs($this->rd)->get('/province-directories/' . Crypt::encrypt($this->theirs->id))->assertForbidden();
    });

    it('lists only the region\'s provinces and directors', function () {
        $provinces = inertiaProps($this->actingAs($this->rd)->get('/province-directories')->assertOk())['provinces'];
        expect(collect($provinces['data'] ?? $provinces)->pluck('name')->all())->toBe(['Pangasinan']);

        $directors = inertiaProps($this->actingAs($this->rd)->get('/provincial-directors')->assertOk())['directors'];
        expect(collect($directors['data'] ?? $directors)->pluck('id')->all())->toBe([$this->myDirector->id]);
    });

    it('keeps the national pages closed', function () {
        $this->actingAs($this->rd)->get('/regions')->assertForbidden();
        $this->actingAs($this->rd)->get('/performance-map')->assertForbidden();
        $this->actingAs($this->rd)->get('/kpi-data')->assertForbidden();
    });

    it('reports official national ranks for the region\'s provinces', function () {
        $props = inertiaProps($this->actingAs($this->rd)->get('/regional-director-report')->assertOk());
        $row   = collect($props['director_rankings'])->firstWhere('province', 'Pangasinan');

        // Cagayan (Region II) scores higher, so Pangasinan is #2 of 2 nationally.
        expect($props['director_rankings'])->toHaveCount(1)
            ->and($row['tier_rank'])->toBe(2)
            ->and($row['tier_size'])->toBe(2);
    });

    it('exports the regional report as a PDF', function () {
        $this->actingAs($this->rd)->get('/regional-director-report/export')
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    });
});
