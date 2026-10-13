<?php

use App\Models\User;

// Editing your own profile: admins edit their names, directors and staff only
// their education; nobody can delete their own account.

beforeEach(function () {
    $this->province = makeProvince();
});

it('shows the profile editor to every role', function ($role) {
    $user = match ($role) {
        'super_admin', 'sub_admin' => makeUser($role),
        'regional_director'        => makeUser($role, null, $this->province->region),
        default                    => makeUser($role, $this->province),
    };
    $this->actingAs($user)->get('/profile')->assertOk()
        ->assertInertia(fn($page) => $page->component('Other/Profile/Edit')->where('role', $role));
})->with(['super_admin', 'sub_admin', 'regional_director', 'provincial_admin', 'provincial_director', 'employee']);

it('lets an admin update their name', function () {
    $admin = makeUser('provincial_admin', $this->province);

    $this->actingAs($admin)->from('/profile')
        ->patch('/profile', ['prefix' => 'Engr.', 'first_name' => 'Maria', 'last_name' => 'Santos'])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $profile = $admin->fresh()->profile;
    expect([$profile->prefix, $profile->first_name, $profile->last_name])->toBe(['Engr.', 'Maria', 'Santos']);
});

it('requires an admin\'s first and last name', function () {
    $admin = makeUser('super_admin');
    $this->actingAs($admin)->patch('/profile', ['first_name' => ''])->assertSessionHasErrors(['first_name', 'last_name']);
});

it('lets directors and staff update only their education', function ($role) {
    $user   = makeUser($role, $this->province);
    $before = $user->profile->first_name;

    $this->actingAs($user)->patch('/profile', ['education' => ['BS Chemistry', 'MS Physics'], 'first_name' => 'Changed'])
        ->assertSessionHasNoErrors();

    $profile = $user->fresh()->profile;
    expect($profile->education_attainment['data'])->toBe(['BS Chemistry', 'MS Physics'])
        ->and($profile->first_name)->toBe($before);
})->with(['provincial_director', 'employee']);

it('does not let anyone delete their own account', function () {
    $user = makeUser('employee', $this->province);
    $this->actingAs($user)->delete('/profile', ['password' => 'password']);

    expect(User::find($user->id))->not->toBeNull();
});
