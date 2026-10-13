<?php

use App\Models\User;

// This app's login rules on top of the standard flow: sign in by email,
// username or DOST employee ID; silent failures; role requirements.

beforeEach(function () {
    $this->province = makeProvince();
});

function attemptLogin(string $identifier, string $password = 'password')
{
    return test()->post('/login', ['identifier' => $identifier, 'password' => $password]);
}

it('signs in by email or username', function ($field) {
    $user = makeUser('employee', $this->province);
    attemptLogin($user->{$field})->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
})->with(['email', 'username']);

it('signs staff in by DOST employee ID', function () {
    $user = makeUser('employee', $this->province, null, ['dost_employee_id' => 'DOST-0042']);
    attemptLogin('DOST-0042')->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

it('does not sign admins in by DOST employee ID', function () {
    makeUser('provincial_admin', $this->province, null, ['dost_employee_id' => 'DOST-0043']);
    attemptLogin('DOST-0043');
    $this->assertGuest();
});

it('fails silently on a wrong password, with no error message', function () {
    $user = makeUser('employee', $this->province);
    attemptLogin($user->email, 'wrong')->assertSessionHasNoErrors();
    $this->assertGuest();
});

it('keeps deactivated accounts out', function () {
    $user = makeUser('employee', $this->province);
    $user->forceFill(['activate' => false])->save();
    attemptLogin($user->email);
    $this->assertGuest();
});

it('signs out an account with no active role, with a message', function () {
    $user = User::factory()->create();
    attemptLogin($user->email)->assertRedirect('/login')->assertSessionHasErrors('identifier');
    $this->assertGuest();
});

it('sends a multi-role user without an active role to the role chooser', function () {
    $user = makeUser('provincial_admin', $this->province);
    $user->forceFill(['active_role_id' => null])->save();
    attemptLogin($user->email)->assertRedirect('/choose-role');
});

it('locks an identifier out after five failed attempts', function () {
    $user = makeUser('employee', $this->province);
    foreach (range(1, 5) as $_) attemptLogin($user->email, 'wrong');

    attemptLogin($user->email); // the right password, but locked out
    $this->assertGuest();
});

it('has no public registration or email verification', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', ['email' => 'x@example.com', 'password' => 'password'])->assertNotFound();
    expect(User::count())->toBe(0);

    $this->actingAs(makeUser('employee', $this->province))->get('/verify-email')->assertNotFound();
});
