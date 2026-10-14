<?php

use App\Models\ActivityLog;
use App\Models\KpiCorrectionRequest as Correction;
use App\Models\KpiReportingDeadline;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;

// The correction request flow for a locked period, over HTTP:
//   provincial admin files → regional director forwards or returns →
//   super admin approves (until a date) or declines → the period reopens,
//   changes are stamped, and released rankings show as revised.

beforeEach(function () {
    $this->kpis          = makeCatalog();
    $this->region        = makeRegion('Region I');
    $this->otherRegion   = makeRegion('Region II');
    $this->province      = makeProvince($this->region, 'Pangasinan', 'large');
    $this->otherProvince = makeProvince($this->otherRegion, 'Cagayan', 'medium');
    $this->director      = makeUser('provincial_director', $this->province);
    $this->admin         = makeUser('provincial_admin', $this->province);
    $this->rd            = makeUser('regional_director', null, $this->region);
    $this->otherRd       = makeUser('regional_director', null, $this->otherRegion);
    $this->central       = makeUser('super_admin');
    $this->year          = (int) now()->year;

    // Q1 is past its deadline, so it's locked.
    lockPeriod($this->year, 1);
});

function lockPeriod(int $year, int $quarter): void
{
    KpiReportingDeadline::create(['year' => $year, 'quarter' => $quarter, 'deadline' => now()->subDay()->toDateString()]);
}

function fileCorrection(?int $quarter = 1, ?int $year = null, string $reason = 'Two approved Q1 proposals were left out of the figures.')
{
    return test()->actingAs(test()->admin)->post('/kpi-corrections', [
        'year' => $year ?? test()->year, 'quarter' => $quarter, 'reason' => $reason,
    ]);
}

function correctionAt(string $status, ?int $quarter = 1, ?int $year = null): Correction
{
    return Correction::create([
        'province_id'  => test()->province->id,
        'year'         => $year ?? test()->year,
        'quarter'      => $quarter,
        'reason'       => 'Two approved Q1 proposals were left out of the figures.',
        'requested_by' => test()->admin->id,
        'status'       => $status,
    ]);
}

function messagesFor(User $user): array
{
    return Notification::where('receiver_id', $user->id)->orderBy('id')->get()
        ->map(fn($n) => $n->content['message'])->all();
}

function saveQ1(User $as, $value = 7, ?int $year = null)
{
    $year ??= test()->year;
    $url = $as->role === 'super_admin'
        ? '/kpi-data/' . Crypt::encrypt(test()->province->id) . "/{$year}"
        : "/provincial-kpi/{$year}";
    return test()->actingAs($as)->put($url, [
        'targets'  => [],
        'quarters' => [1 => [test()->kpis['core_proposals']->id => $value]],
    ]);
}

describe('filing a request', function () {
    it('files a request for a locked quarter and notifies the regional director', function () {
        fileCorrection()->assertSessionHasNoErrors();

        $c = Correction::sole();
        expect($c->status)->toBe(Correction::PENDING_REGIONAL)
            ->and($c->quarter)->toBe(1)
            ->and($c->requested_by)->toBe($this->admin->id)
            ->and(messagesFor($this->rd))->toHaveCount(1)
            ->and(messagesFor($this->rd)[0])->toContain('Pangasinan requested a correction to Q1')
            ->and(messagesFor($this->otherRd))->toBeEmpty()
            ->and(messagesFor($this->central))->toBeEmpty()
            ->and(ActivityLog::where('description', 'like', '%requested the correction request for Pangasinan Q1%')->exists())->toBeTrue();
    });

    it('refuses a quarter that is still open', function () {
        fileCorrection(2)->assertSessionHasErrors(['period']);
        expect(Correction::count())->toBe(0);
    });

    it('allows one request in progress per period, and a new one after a decline', function () {
        fileCorrection()->assertSessionHasNoErrors();
        fileCorrection()->assertSessionHasErrors(['period']);

        Correction::sole()->update(['status' => Correction::DECLINED]);
        fileCorrection()->assertSessionHasNoErrors();
        expect(Correction::count())->toBe(2);
    });

    it('needs a reason of at least 10 characters', function () {
        fileCorrection(reason: 'fix it')->assertSessionHasErrors(['reason']);
    });

    it('asks for targets only once the Q4 deadline has passed', function () {
        fileCorrection(null)->assertSessionHasErrors(['period']);

        lockPeriod($this->year, 4);
        fileCorrection(null)->assertSessionHasNoErrors();
        expect(Correction::sole()->quarter)->toBeNull()
            ->and(Correction::sole()->periodLabel())->toBe("{$this->year} targets");
    });

    it('is filed only by the provincial admin', function () {
        foreach ([$this->director, $this->rd, $this->central] as $user) {
            $this->actingAs($user)->post('/kpi-corrections', ['year' => $this->year, 'quarter' => 1, 'reason' => 'Some long enough reason.'])
                ->assertForbidden();
        }
    });
});

describe('regional director review', function () {
    it('forwards to the central office with a note', function () {
        $c = correctionAt(Correction::PENDING_REGIONAL);

        $this->actingAs($this->rd)->post("/kpi-corrections/{$c->id}/forward", ['note' => 'Confirmed with the province.'])
            ->assertSessionHasNoErrors();

        $c->refresh();
        expect($c->status)->toBe(Correction::PENDING_CENTRAL)
            ->and($c->regional_reviewer_id)->toBe($this->rd->id)
            ->and($c->regional_note)->toBe('Confirmed with the province.')
            ->and(messagesFor($this->central)[0])->toContain("forwarded Pangasinan's correction request for Q1")
            ->and(messagesFor($this->admin)[0])->toContain('was forwarded to the central office');
    });

    it('returns it with a required note, and the province resubmits', function () {
        $c = correctionAt(Correction::PENDING_REGIONAL);

        $this->actingAs($this->rd)->post("/kpi-corrections/{$c->id}/return", ['note' => ''])->assertSessionHasErrors(['note']);
        $this->actingAs($this->rd)->post("/kpi-corrections/{$c->id}/return", ['note' => 'Which proposals? List them.'])
            ->assertSessionHasNoErrors();
        expect($c->fresh()->status)->toBe(Correction::RETURNED)
            ->and(messagesFor($this->admin)[0])->toContain('returned by the regional director');

        // Still blocks a second request for the same quarter while it's with the province.
        fileCorrection()->assertSessionHasErrors(['period']);

        $this->actingAs($this->admin)->patch("/kpi-corrections/{$c->id}/resubmit", ['reason' => 'Proposals P-101 and P-102 were left out of Q1.'])
            ->assertSessionHasNoErrors();
        expect($c->fresh()->status)->toBe(Correction::PENDING_REGIONAL)
            ->and($c->fresh()->reason)->toBe('Proposals P-101 and P-102 were left out of Q1.')
            ->and(messagesFor($this->rd))->toHaveCount(1)
            ->and(messagesFor($this->rd)[0])->toContain('resubmitted');
    });

    it('keeps every step in the history, so a return is still shown once forwarded', function () {
        fileCorrection()->assertSessionHasNoErrors();
        $c = Correction::sole();
        $this->actingAs($this->rd)->post("/kpi-corrections/{$c->id}/return", ['note' => 'Which proposals?']);
        $this->actingAs($this->admin)->patch("/kpi-corrections/{$c->id}/resubmit", ['reason' => 'Proposals P-101 and P-102.']);
        $this->actingAs($this->rd)->post("/kpi-corrections/{$c->id}/forward", ['note' => 'Checked.']);
        $this->actingAs($this->central)->post("/kpi-corrections/{$c->id}/approve", ['open_until' => now()->addDay()->toDateString()]);

        $history = inertiaProps($this->actingAs($this->central)->get('/kpi-corrections'))['requests'][0]['history'];
        expect(collect($history)->pluck('step')->all())->toBe(['requested', 'returned', 'resubmitted', 'forwarded', 'approved'])
            ->and($history[0]['note'])->toBe('Two approved Q1 proposals were left out of the figures.')
            ->and($history[1]['note'])->toBe('Which proposals?')
            ->and($history[3]['note'])->toBe('Checked.')
            ->and($history[4]['open_until'])->toBe(now()->addDay()->toDateString());
    });

    it('cannot act on another region\'s requests', function () {
        $c = correctionAt(Correction::PENDING_REGIONAL);
        $this->actingAs($this->otherRd)->post("/kpi-corrections/{$c->id}/forward")->assertForbidden();
        $this->actingAs($this->otherRd)->post("/kpi-corrections/{$c->id}/return", ['note' => 'Not mine to judge.'])->assertForbidden();
        expect($c->fresh()->status)->toBe(Correction::PENDING_REGIONAL);
    });

    it('takes each step only once', function () {
        $c = correctionAt(Correction::PENDING_CENTRAL);
        $this->actingAs($this->rd)->post("/kpi-corrections/{$c->id}/forward")->assertSessionHasErrors(['status']);
    });

    it('lists only their region\'s requests; the super admin sees every one', function () {
        correctionAt(Correction::PENDING_REGIONAL);
        Correction::create([
            'province_id' => $this->otherProvince->id, 'year' => $this->year, 'quarter' => 1,
            'reason' => 'Another region entirely.', 'status' => Correction::PENDING_REGIONAL,
        ]);

        $regional = inertiaProps($this->actingAs($this->rd)->get('/kpi-corrections')
            ->assertOk()->assertInertia(fn($p) => $p->component('Kpi/Corrections')));
        expect(collect($regional['requests'])->pluck('province')->all())->toBe(['Pangasinan'])
            ->and($regional['mode'])->toBe('regional')
            ->and($regional['region'])->toBe('Region I');

        $central = inertiaProps($this->actingAs($this->central)->get('/kpi-corrections')->assertOk());
        expect($central['requests'])->toHaveCount(2)->and($central['mode'])->toBe('central');
    });

    it('is not open to the province or sub admins', function () {
        $this->actingAs($this->admin)->get('/kpi-corrections')->assertForbidden();
        $this->actingAs(makeUser('sub_admin'))->get('/kpi-corrections')->assertForbidden();
    });
});

describe('central office decision', function () {
    it('approves until a chosen date: only that quarter reopens, through the end of that day', function () {
        $c     = correctionAt(Correction::PENDING_CENTRAL);
        $until = now()->addDays(3)->toDateString();

        $this->actingAs($this->central)->post("/kpi-corrections/{$c->id}/approve", ['open_until' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors(['open_until']);
        $this->actingAs($this->central)->post("/kpi-corrections/{$c->id}/approve", ['open_until' => $until, 'note' => 'Go ahead.'])
            ->assertSessionHasNoErrors();

        $c->refresh();
        expect($c->status)->toBe(Correction::APPROVED)
            ->and($c->open_until->toDateTimeString())->toBe("{$until} 23:59:59")
            ->and($c->central_note)->toBe('Go ahead.')
            ->and(messagesFor($this->admin)[0])->toContain('Correction approved: Q1')
            ->and(messagesFor($this->director)[0])->toContain('Correction approved: Q1')
            ->and(messagesFor($this->rd)[0])->toContain("approved Pangasinan's correction");

        // The province can now change Q1 (and the change is stamped), not Q2's neighbours.
        saveQ1($this->admin)->assertSessionHasNoErrors();
        expect($c->fresh()->applied_at)->not->toBeNull()
            ->and($c->fresh()->revised_at)->toBeNull();   // this year isn't released yet

        // After the chosen day it locks again.
        $this->travelTo(now()->addDays(4));
        saveQ1($this->admin, 9)->assertSessionHasErrors(['quarters']);
    });

    it('declines with a required reason; the quarter stays locked', function () {
        $c = correctionAt(Correction::PENDING_CENTRAL);

        $this->actingAs($this->central)->post("/kpi-corrections/{$c->id}/decline", ['note' => ''])->assertSessionHasErrors(['note']);
        $this->actingAs($this->central)->post("/kpi-corrections/{$c->id}/decline", ['note' => 'The deadline was announced in January.'])
            ->assertSessionHasNoErrors();

        expect($c->fresh()->status)->toBe(Correction::DECLINED)
            ->and(messagesFor($this->admin)[0])->toContain('Correction declined for Q1')
            ->and(messagesFor($this->rd)[0])->toContain("declined Pangasinan's correction");
        saveQ1($this->admin)->assertSessionHasErrors(['quarters']);
    });

    it('decides only on requests the regional director has forwarded', function () {
        $c = correctionAt(Correction::PENDING_REGIONAL);
        $this->actingAs($this->central)->post("/kpi-corrections/{$c->id}/approve", ['open_until' => now()->addDay()->toDateString()])
            ->assertSessionHasErrors(['status']);
        expect($c->fresh()->status)->toBe(Correction::PENDING_REGIONAL);
    });

    it('keeps each role to its own step', function () {
        $c = correctionAt(Correction::PENDING_CENTRAL);
        $this->actingAs($this->rd)->post("/kpi-corrections/{$c->id}/approve", ['open_until' => now()->addDay()->toDateString()])->assertForbidden();
        $this->actingAs($this->central)->post("/kpi-corrections/{$c->id}/forward")->assertForbidden();
        $this->actingAs($this->admin)->post("/kpi-corrections/{$c->id}/decline", ['note' => 'Self-declined.'])->assertForbidden();
    });

    it('gives the super admin no way to unlock a period without a request', function () {
        saveQ1($this->central)->assertSessionHasErrors(['quarters']);
    });
});

describe('revised rankings', function () {
    it('marks a released year as revised when a correction changes it, on every dashboard', function () {
        // Last year: no Q4 deadline and the year is over, so its rankings are out.
        $last = $this->year - 1;
        setAnnual($this->province, $this->kpis['core_proposals'], $last, 10, 5);
        lockPeriod($last, 1);
        $c = correctionAt(Correction::APPROVED, 1, $last);
        $c->update(['open_until' => now()->addDay()]);

        saveQ1($this->admin, 8, $last)->assertSessionHasNoErrors();
        expect($c->fresh()->revised_at)->not->toBeNull();

        $national = inertiaProps($this->actingAs($this->central)->get('/dashboard'));
        expect($national['revisions'][$last]['provinces'][0])->toMatchArray([
            'province_id' => $this->province->id, 'province' => 'Pangasinan', 'periods' => ["Q1 {$last}"],
        ]);

        // A regional director outside the region still sees the year as revised,
        // without the other region's province named.
        $other = inertiaProps($this->actingAs($this->otherRd)->get('/dashboard'));
        expect($other['revisions'][$last]['at'])->not->toBeNull()
            ->and($other['revisions'][$last]['provinces'])->toBeEmpty();

        $pd = inertiaProps($this->actingAs($this->director)->get('/dashboard'));
        expect($pd['standing']['year'])->toBe($last)
            ->and($pd['standing']['revised_at'])->not->toBeNull();
    });

    it('shows nothing revised when no correction changed released figures', function () {
        $national = inertiaProps($this->actingAs($this->central)->get('/dashboard'));
        expect($national['revisions'])->toBeEmpty();
    });
});

describe('screens and notifications', function () {
    it('gives the entry page this year\'s requests; only the provincial admin can file', function () {
        correctionAt(Correction::RETURNED);

        $admin = inertiaProps($this->actingAs($this->admin)->get('/provincial-kpi'));
        expect($admin['corrections'])->toHaveCount(1)
            ->and($admin['corrections'][0])->toMatchArray(['period' => "Q1 {$this->year}", 'status' => 'returned', 'status_label' => 'Returned to province'])
            ->and($admin['can_request'])->toBeTrue();

        $director = inertiaProps($this->actingAs($this->director)->get('/provincial-kpi'));
        expect($director['corrections'])->toHaveCount(1)->and($director['can_request'])->toBeFalse();
    });

    it('shows reopened targets on the entry page', function () {
        lockPeriod($this->year, 4);
        correctionAt(Correction::APPROVED, null)->update(['open_until' => now()->addDays(2)]);

        $props = inertiaProps($this->actingAs($this->admin)->get('/provincial-kpi'));
        expect($props['targets_open'])->toBeTrue()
            ->and($props['targets_reopened_until'])->not->toBeNull();
    });

    it('counts the requests waiting on each reviewer for the sidebar', function () {
        correctionAt(Correction::PENDING_REGIONAL);
        correctionAt(Correction::PENDING_CENTRAL, 2);

        expect(inertiaProps($this->actingAs($this->rd)->get('/dashboard'))['correction_inbox'])->toBe(1)
            ->and(inertiaProps($this->actingAs($this->otherRd)->get('/dashboard'))['correction_inbox'])->toBe(0)
            ->and(inertiaProps($this->actingAs($this->central)->get('/dashboard'))['correction_inbox'])->toBe(1);
    });

    it('feeds the bell on every page, and marks notifications read', function () {
        fileCorrection();
        $feed = inertiaProps($this->actingAs($this->rd)->get('/dashboard'))['notifications'];
        expect($feed['unread'])->toBe(1)
            ->and($feed['items'][0])->toMatchArray(['type' => 'correction', 'url' => '/kpi-corrections', 'read' => false]);

        $id = $feed['items'][0]['id'];
        $this->actingAs($this->central)->post("/notifications/{$id}/read")->assertForbidden();
        $this->actingAs($this->rd)->post("/notifications/{$id}/read")->assertRedirect();
        expect(inertiaProps($this->actingAs($this->rd)->get('/dashboard'))['notifications']['unread'])->toBe(0);
    });

    it('marks all notifications read at once', function () {
        fileCorrection();
        Correction::sole()->update(['status' => Correction::DECLINED]);
        fileCorrection();

        expect(Notification::where('receiver_id', $this->rd->id)->whereNull('read_since')->count())->toBe(2);
        $this->actingAs($this->rd)->post('/notifications/read-all')->assertRedirect();
        expect(Notification::where('receiver_id', $this->rd->id)->whereNull('read_since')->count())->toBe(0);
    });
});
