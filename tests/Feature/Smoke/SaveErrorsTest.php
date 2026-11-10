<?php

use App\Exceptions\FriendlyErrors;
use App\Models\BlogPost;
use App\Models\FacebookPost;
use App\Models\KpiCatalogSnapshot;
use App\Models\KpiCorrectionRequest;
use App\Models\Linkage;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;

// Saving never shows people a raw server error (SQL, PHP). Every save route is
// sent a blank form by every role, and the main forms are sent with only their
// required fields filled, optional ones blank or left out: none may crash.
// Errors that do happen reach people as plain sentences (FriendlyErrors).

beforeEach(function () {
    $this->kpis     = makeCatalog();
    $this->region   = makeRegion('Region I');
    $this->province = makeProvince($this->region, 'Pangasinan');
    $this->users    = [
        'super_admin'         => makeUser('super_admin'),
        'sub_admin'           => makeUser('sub_admin'),
        'regional_director'   => makeUser('regional_director', null, $this->region),
        'provincial_admin'    => makeUser('provincial_admin', $this->province),
        'provincial_director' => makeUser('provincial_director', $this->province),
        'employee'            => makeUser('employee', $this->province),
    ];
    $this->year = (int) now()->year;
});

// The two ways a form can leave its optional fields empty.
function withOptionalEmpty(array $required, array $optional): array
{
    return [
        'optional fields blank'    => $required + array_fill_keys($optional, ''),
        'optional fields left out' => $required,
    ];
}

function expectSaved($response, string $what): void
{
    $errors = session('errors')?->all() ?? [];
    $detail = str_contains((string) $response->headers->get('content-type'), 'json') ? json_encode($response->json()) : json_encode($errors);

    expect($response->status())->toBeLessThan(400, "{$what} → {$response->status()} {$detail}")
        ->and($errors)->toBe([], "{$what} came back with errors");
}

describe('every save route', function () {
    it('answers a blank form from any role without a server error', function () {
        Exceptions::fake();

        $director = $this->users['provincial_director'];
        $blog     = BlogPost::create(['title' => 'Hello', 'slug' => 'hello', 'tag' => 'Other', 'excerpt' => 'x', 'body' => '<p>x</p>', 'created_by' => $this->users['super_admin']->id]);
        $post     = FacebookPost::create(['province_id' => $this->province->id, 'provincial_director_id' => $director->id, 'year' => $this->year, 'title' => 'Post', 'post_type' => 'Text', 'date_posted' => now()->toDateString()]);
        $linkage  = Linkage::create(['province_id' => $this->province->id, 'provincial_director_id' => $director->id, 'year' => $this->year, 'partner_organization' => 'DTI', 'title' => 'MOA', 'type' => 'MOA', 'date_signed' => now()->toDateString()]);
        $snapshot = KpiCatalogSnapshot::create(['label' => 'v1', 'payload' => ['kpis' => [], 'categories' => []], 'created_by' => $this->users['super_admin']->id]);
        $request  = KpiCorrectionRequest::create(['province_id' => $this->province->id, 'year' => $this->year, 'quarter' => 1, 'reason' => 'Figures were left out.', 'requested_by' => $this->users['provincial_admin']->id, 'status' => KpiCorrectionRequest::PENDING_REGIONAL]);

        $param = fn (string $uri, string $name) => match (true) {
            $name === 'id' && str_starts_with($uri, 'users/')    => $this->users['employee']->id,
            $name === 'id' && str_starts_with($uri, 'blogs/')    => $blog->id,
            $name === 'id' && str_starts_with($uri, 'kpi-data/') => $this->province->id,
            $name === 'director'   => $director->id,
            $name === 'year'       => $this->year,
            $name === 'quarter'    => 1,
            $name === 'roleId'     => Role::where('name', 'employee')->value('id'),
            $name === 'kpi'        => $this->kpis['core_proposals']->id,
            $name === 'post'       => $post->id,
            $name === 'linkage'    => $linkage->id,
            $name === 'snapshot'   => $snapshot->id,
            $name === 'correction' => $request->id,
            default                => 999999,
        };

        // Left out: maintenance actions work on files, the .env and the real
        // server (backups, cache, reset); signing out and deleting one's own
        // account end the session the sweep runs in.
        $skip = fn (string $method, string $uri) => str_starts_with($uri, 'maintenance/')
            || str_starts_with($uri, 'admin/login/') || $uri === 'logout'
            || ($method === 'DELETE' && $uri === 'profile');

        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => str_starts_with($r->getActionName(), 'App\\'))
            ->flatMap(fn ($r) => collect($r->methods())->diff(['GET', 'HEAD'])->map(fn ($m) => [$m, $r->uri()]))
            ->reject(fn ($r) => $skip(...$r))
            ->sortBy(fn ($r) => $r[0] === 'DELETE') // removals last, so the others still find their records
            ->values();

        expect($routes->count())->toBeGreaterThan(40);

        foreach ($routes as [$method, $uri]) {
            $url = '/' . preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => $param($uri, $m[1]), $uri);
            foreach ($this->users as $role => $user) {
                $response = $this->actingAs($user)->json($method, $url, []);
                $isJson   = str_contains((string) $response->headers->get('content-type'), 'json');
                expect($response->status())->toBeLessThan(500, "{$role}: {$method} {$url} → {$response->status()} " . ($isJson ? $response->json('debug') : ''));
            }
        }

        Exceptions::assertNothingReported();
    });
});

describe('forms with optional fields left empty', function () {
    it('saves Edit User (super admin)', function () {
        $user = $this->users['employee'];
        foreach (withOptionalEmpty(
            ['dost_employee_id' => 'EMP-0001', 'email' => 'emp@dost.test', 'first_name' => 'Ana', 'last_name' => 'Cruz'],
            ['province_id', 'password', 'password_confirmation', 'prefix', 'middle_name', 'suffix', 'length_of_service'],
        ) as $how => $payload) {
            expectSaved($this->actingAs($this->users['super_admin'])->putJson("/users/{$user->id}", $payload), "Edit User, {$how}");
        }
        expect($user->profile()->value('length_of_service'))->toBe('');
    });

    it('saves Edit User for an account that has no profile yet', function () {
        $user = $this->users['employee'];
        $user->profile->employeeProfile()->delete();
        $user->profile()->delete();

        expectSaved($this->actingAs($this->users['super_admin'])->putJson("/users/{$user->id}", [
            'dost_employee_id' => 'EMP-0002', 'email' => 'new@dost.test', 'first_name' => 'Ana', 'last_name' => 'Cruz',
        ]), 'Edit User without a profile');
        expect($user->profile()->value('first_name'))->toBe('Ana');
    });

    it('registers a user (super admin and provincial admin)', function () {
        $n = 0;
        foreach (['super_admin', 'provincial_admin'] as $role) {
            foreach (withOptionalEmpty(
                ['registration_type' => 'manual', 'province' => $this->province->id, 'prefix' => 'Ms.', 'first_name' => 'Ana', 'last_name' => 'Cruz',
                 'password' => 'password123', 'password_confirmation' => 'password123', 'position' => 'SRS I', 'status' => 'permanent', 'role' => 'employee'],
                ['middle_name', 'suffix', 'length_of_service', 'admin_role'],
            ) as $how => $payload) {
                $n++;
                $payload += ['dost_employee_id' => "REG-{$n}", 'email' => "reg{$n}@dost.test"];
                expectSaved($this->actingAs($this->users[$role])->postJson('/users/store', $payload), "Register ({$role}), {$how}");
            }
        }
    });

    it('saves Edit Profile (admin and employee)', function () {
        foreach (withOptionalEmpty(['first_name' => 'Ana', 'last_name' => 'Cruz'], ['prefix', 'middle_name', 'suffix', 'length_of_service']) as $how => $payload) {
            expectSaved($this->actingAs($this->users['sub_admin'])->patch('/profile', $payload), "Edit Profile (admin), {$how}");
        }
        foreach (withOptionalEmpty([], ['education']) as $how => $payload) {
            expectSaved($this->actingAs($this->users['employee'])->patch('/profile', $payload), "Edit Profile (employee), {$how}");
        }
    });

    it('saves a blog post as a draft when "published" is not sent', function () {
        $as = $this->actingAs($this->users['super_admin']);
        foreach (withOptionalEmpty(['title' => 'Hello', 'tag' => 'Other', 'excerpt' => 'Short', 'body' => '<p>Body</p>'], ['image', 'is_published']) as $how => $payload) {
            unset($payload['is_published']); // "" is not a yes/no answer; the form always sends true/false or nothing
            expectSaved($as->post('/blogs', $payload), "New blog post, {$how}");
            expectSaved($as->post('/blogs/' . BlogPost::latest('id')->value('id'), $payload), "Edit blog post, {$how}");
        }
        expect(BlogPost::whereNotNull('published_at')->count())->toBe(0);
    });

    it('saves Facebook posts and linkages', function () {
        $director = $this->users['provincial_director'];
        foreach (withOptionalEmpty(['title' => 'Fair', 'post_type' => 'Photo', 'date_posted' => now()->toDateString()], ['post_url', 'caption']) as $how => $payload) {
            expectSaved($this->actingAs($this->users['provincial_admin'])->post("/facebook-posts/{$director->id}/{$this->year}", $payload), "Facebook post, {$how}");
        }
        foreach (withOptionalEmpty(['partner_organization' => 'DTI', 'title' => 'Tech transfer', 'type' => 'MOA', 'date_signed' => now()->toDateString()], ['signatories', 'remarks']) as $how => $payload) {
            expectSaved($this->actingAs($this->users['provincial_admin'])->post("/linkages/{$director->id}/{$this->year}", $payload), "Linkage, {$how}");
        }
    });

    it('saves the province information page', function () {
        foreach (withOptionalEmpty([], ['description', 'image', 'remove_image']) as $how => $payload) {
            unset($payload['remove_image']);
            expectSaved($this->actingAs($this->users['provincial_admin'])->put('/province-info', $payload), "Province info, {$how}");
        }
    });

    it('saves KPI figures, deadlines, catalog versions and catalog edits', function () {
        $kpi = $this->kpis['core_proposals'];

        expectSaved($this->actingAs($this->users['provincial_admin'])->put("/provincial-kpi/{$this->year}", [
            'targets' => [$kpi->id => ''], 'quarters' => [$kpi->id => [1 => '', 2 => null]],
        ]), 'KPI figures with blank cells');

        expectSaved($this->actingAs($this->users['super_admin'])->put("/kpi-deadlines/{$this->year}", [
            'deadlines' => [1 => '', 2 => null],
        ]), 'Deadlines left blank');

        foreach (withOptionalEmpty(['label' => 'Before changes'], ['note']) as $how => $payload) {
            expectSaved($this->actingAs($this->users['super_admin'])->post('/kpi-catalog/snapshots', $payload), "Catalog version, {$how}");
        }

        foreach (withOptionalEmpty(['id' => $kpi->id, 'name' => $kpi->name, 'category_id' => $kpi->category_id, 'weight' => 10], ['rollup_rule']) as $how => $change) {
            expectSaved($this->actingAs($this->users['super_admin'])->put('/kpi-catalog/kpis', ['changes' => [$change]]), "Catalog edit, {$how}");
        }
    });

    it('files and forwards a correction request without the optional parts', function () {
        // Corrections are for closed periods: every quarter's deadline has passed.
        foreach (range(1, 4) as $q) {
            App\Models\KpiReportingDeadline::create(['year' => $this->year, 'quarter' => $q, 'deadline' => now()->subDay()->toDateString()]);
        }

        expectSaved($this->actingAs($this->users['provincial_admin'])->post('/kpi-corrections', [
            'year' => $this->year, 'quarter' => '', 'reason' => 'Two proposals were left out of the figures.',
        ]), 'Correction request without a quarter');

        $request = KpiCorrectionRequest::latest('id')->first();
        expectSaved($this->actingAs($this->users['regional_director'])->post("/kpi-corrections/{$request->id}/forward", ['note' => '']), 'Forward without a note');
    });
});

describe('the wording people see when a save fails', function () {
    beforeEach(function () {
        $user = $this->users['employee'];
        Route::middleware(['web', 'auth'])->group(function () use ($user) {
            Route::post('/__test/blank', fn () => DB::table('profiles')->where('user_id', $user->id)->update(['length_of_service' => null]));
            Route::post('/__test/taken', fn () => DB::table('users')->where('id', $user->id)->update(['email' => test()->users['super_admin']->email]));
            Route::post('/__test/crash', fn () => throw new RuntimeException('Call to a member function update() on null'));
            Route::post('/__test/missing', fn () => App\Models\User::findOrFail(999999));
            Route::post('/__test/forbidden', fn () => abort(403, 'Only the regional director can forward this.'));
        });
    });

    it('names the field and says what to do', function (string $url, int $status, string $message, ?string $field) {
        $response = $this->actingAs($this->users['super_admin'])->postJson($url);

        $response->assertStatus($status)->assertJsonPath('message', $message);
        if ($field) $response->assertJsonPath("errors.{$field}.0", $message);
    })->with([
        'a required field left blank' => ['/__test/blank', 422, "Length of service can't be left blank. Please fill it in and try again.", 'length_of_service'],
        'a value already in use'      => ['/__test/taken', 422, 'Email is already in use. Please enter a different one.', 'email'],
        'a record removed meanwhile'  => ['/__test/missing', 404, 'This record no longer exists. It may have been removed by someone else. Refresh the page and try again.', null],
        'any other failure'           => ['/__test/crash', 500, FriendlyErrors::GENERIC, null],
        'a reason written on purpose' => ['/__test/forbidden', 403, 'Only the regional director can forward this.', null],
    ]);

    it('sends Inertia saves back to the form with the message as an error', function () {
        $this->actingAs($this->users['super_admin'])
            ->from('/users')
            ->post('/__test/blank', [], ['X-Inertia' => 'true'])
            ->assertRedirect('/users')
            ->assertSessionHasErrors(['length_of_service' => "Length of service can't be left blank. Please fill it in and try again."]);
    });

    it('keeps SQL and PHP text out of every response', function (string $url) {
        $body = $this->actingAs($this->users['super_admin'])->postJson($url)->json();
        unset($body['debug']); // developers only; the page never shows it

        expect(json_encode($body))->not->toMatch('/SQLSTATE|constraint|on null|App\\\\\\\\Models/');
    })->with(['/__test/blank', '/__test/taken', '/__test/crash', '/__test/missing']);
});
