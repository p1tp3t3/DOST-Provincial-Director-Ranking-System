<?php

use App\Helpers\ActivityLogHelper;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// Every page in each role's menu (plus the report exports) loads without an
// error, on a small but realistic world: two regions, three provinces with
// KPI figures, and one user of every role.

beforeEach(function () {
    $kpis    = makeCatalog();
    $region  = makeRegion('Region I');
    $region2 = makeRegion('Region II');

    $this->province = makeProvince($region, 'Pangasinan', 'large');
    $provinces      = [$this->province, makeProvince($region, 'La Union', 'small'), makeProvince($region2, 'Cagayan', 'medium')];
    foreach ($provinces as $i => $p) {
        makeUser('provincial_director', $p);
        makeUser('employee', $p);
        setAnnual($p, $kpis['core_proposals'], 2025, 10, 5 + $i);
        setAnnual($p, $kpis['core_gross_sales'], 2024, 100, 80);
    }

    $this->users = [
        'super_admin'         => makeUser('super_admin'),
        'sub_admin'           => makeUser('sub_admin'),
        'regional_director'   => makeUser('regional_director', null, $region),
        'provincial_admin'    => makeUser('provincial_admin', $this->province),
        'provincial_director' => User::whereRole('provincial_director')->whereProvince($this->province->id)->first(),
        'employee'            => User::whereRole('employee')->whereProvince($this->province->id)->first(),
    ];

    // Some activity, so log pages and reports have rows to render.
    foreach ($this->users as $user) {
        Auth::login($user);
        ActivityLogHelper::login();
    }
    Auth::logout();
});

it('loads every page in the role\'s menu', function (string $role, array $pages) {
    foreach ($pages as $page) {
        $response = $this->actingAs($this->users[$role])->get($page);
        expect($response->status())->toBe(200, "{$role} → {$page} returned {$response->status()}");
    }
})->with([
    'super admin' => ['super_admin', [
        '/dashboard', '/regions', '/province-directories', '/kpi-data', '/kpi-catalog', '/blogs', '/blogs/create',
        '/users', '/users/create', '/admins', '/maintenance', '/activity-logs', '/super-admin-report',
        '/performance-map', '/settings', '/profile',
    ]],
    'sub admin' => ['sub_admin', [
        '/dashboard', '/regions', '/province-directories', '/kpi-data', '/kpi-catalog', '/blogs', '/employees',
        '/sub-admin-report', '/performance-map', '/settings', '/profile',
    ]],
    'regional director' => ['regional_director', [
        '/dashboard', '/province-directories', '/users', '/activity-logs', '/regional-director-report',
        '/regional-performance-map', '/provincial-directors', '/settings', '/profile',
    ]],
    'provincial admin' => ['provincial_admin', [
        '/dashboard', '/users', '/users/create', '/users/auto-generator', '/provincial-kpi', '/provincial-kpi/2025',
        '/province-info', '/employees', '/activity-logs', '/provincial-admin-report', '/provincial-sub-admin-report',
        '/settings', '/profile',
    ]],
    'provincial director' => ['provincial_director', [
        '/dashboard', '/provincial-kpi', '/employees', '/provincial-director-report', '/settings', '/profile',
    ]],
    'employee' => ['employee', ['/dashboard', '/provincial-directors', '/settings', '/profile']],
]);

it('exports each role\'s report as a PDF', function (string $role, string $url) {
    $this->actingAs($this->users[$role])->get($url)
        ->assertOk()->assertHeader('content-type', 'application/pdf');
})->with([
    ['super_admin',         '/super-admin-report/export'],
    ['sub_admin',           '/sub-admin-report/export'],
    ['regional_director',   '/regional-director-report/export'],
    ['provincial_admin',    '/provincial-admin-report/export'],
    ['provincial_admin',    '/provincial-sub-admin-report/export'],
    ['provincial_director', '/provincial-director-report/export'],
]);

it('serves the activity log report for a date range', function ($role) {
    $range = '?date_from=' . now()->subDay()->toDateString() . '&date_to=' . now()->toDateString();
    $this->actingAs($this->users[$role])->get('/activity-logs/report' . $range)->assertOk();
    $this->actingAs($this->users[$role])->get('/activity-logs/report')->assertSessionHasErrors(['date_from', 'date_to']);
})->with(['super_admin', 'regional_director', 'provincial_admin']);

it('loads the public pages', function ($page) {
    $this->get($page)->assertOk();
})->with(['/', '/map', '/login', '/forgot-password']);

it('opens a province profile for the roles that can', function () {
    $id = \Illuminate\Support\Facades\Crypt::encrypt($this->province->id);
    foreach (['super_admin', 'sub_admin', 'regional_director', 'provincial_admin'] as $role) {
        $this->actingAs($this->users[$role])->get("/province-directories/{$id}")->assertOk();
    }
});
