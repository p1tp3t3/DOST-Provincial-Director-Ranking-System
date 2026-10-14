<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['dost_employee_id', 'username', 'email', 'password', 'activate', 'region_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasPushSubscriptions;

    // 'role' is appended (not a real column) so it still shows up in
    // toArray()/JSON for the frontend — see getRoleAttribute() below.
    protected $appends = ['province', 'role'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'activate'          => 'boolean',
        ];
    }

    public function profile() {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }

    public function region() {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }

    public function provinces() {
        return $this->belongsToMany(Province::class, 'user_province', 'user_id', 'province_id')
                     ->using(UserProvince::class);
    }

    // Most roles are tied to a single province; this convenience accessor returns
    // the first (and usually only) related province.
    public function getProvinceAttribute() {
        return $this->provinces->first();
    }

    public function getProvinceIdAttribute() {
        return $this->province?->id;
    }

    public function scopeWhereProvince(Builder $query, ?int $provinceId): Builder {
        return $query->whereHas('provinces', fn($q) => $q->where('provinces.id', $provinceId));
    }

    public function scopeWhereProvinceIn(Builder $query, array $provinceIds): Builder {
        return $query->whereHas('provinces', fn($q) => $q->whereIn('provinces.id', $provinceIds));
    }

    // ── Roles ────────────────────────────────────────────────────────
    // A user can hold several roles (e.g. employee + one admin-tier role).
    // Exactly one is "active" at a time — the hat they're currently wearing,
    // which drives the sidebar/dashboard/permissions exactly like the old
    // single `role` column did. See Role::ADMIN_ROLES for the one-admin-max rule.

    public function roles() {
        return $this->belongsToMany(Role::class, 'user_roles')
                     ->using(UserRole::class)
                     ->withPivot(['locked'])
                     ->withTimestamps();
    }

    public function activeRole() {
        return $this->belongsTo(Role::class, 'active_role_id');
    }

    // Drop-in replacement for the old `role` column: resolves to the name of
    // whichever role is currently active. Works via plain PHP property access
    // (so Collection::where('role', ...)/groupBy('role') on already-loaded
    // models keep working too) — only raw DB queries against a `role` column
    // need rewriting to scopeWhereRole()/scopeWhereRoleIn() below.
    public function getRoleAttribute(): ?string {
        return $this->activeRole?->name;
    }

    public function scopeWhereRole(Builder $query, string $roleName): Builder {
        return $query->whereHas('roles', fn($q) => $q->where('roles.name', $roleName)->where('user_roles.locked', true));
    }

    public function scopeWhereRoleIn(Builder $query, array $roleNames): Builder {
        return $query->whereHas('roles', fn($q) => $q->whereIn('roles.name', $roleNames)->where('user_roles.locked', true));
    }

    // Roles currently in effect (locked) — eligible to be the active role.
    public function lockedRoles() {
        return $this->roles->filter(fn($r) => $r->pivot->locked)->values();
    }

    public function hasRole(string $name): bool {
        return $this->roles->contains('name', $name);
    }

    public function hasLockedRole(string $name): bool {
        return $this->lockedRoles()->contains('name', $name);
    }

    // The single place a role gets attached to a user — enforces "at most one
    // admin-tier role, plus optionally employee" and auto-promotes the role to
    // active if the user doesn't have an active one yet (covers both brand-new
    // users and a user whose only locked role was just unlocked elsewhere).
    public function assignRole(string $name, bool $locked = true): void {
        $role = Role::where('name', $name)->firstOrFail();

        if (in_array($name, Role::ADMIN_ROLES, true)) {
            $existingAdmin = $this->roles()
                ->whereIn('roles.name', Role::ADMIN_ROLES)
                ->where('roles.name', '!=', $name)
                ->first();

            if ($existingAdmin) {
                throw ValidationException::withMessages([
                    'role' => "User already has an admin role: {$existingAdmin->name}.",
                ]);
            }
        }

        if ($locked && $this->activate !== false) {
            $this->assertDirectorSlotFree($name);
        }

        $this->roles()->syncWithoutDetaching([$role->id => ['locked' => $locked]]);

        if ($locked && !$this->active_role_id) {
            $this->forceFill(['active_role_id' => $role->id])->save();
        }

        // Every account is an employee first — an admin-tier role sits on top
        // of that, it never replaces it. Attached quietly (never promoted to
        // active here) so it doesn't disturb whichever role was just set above.
        if ($name !== 'employee') {
            $employeeRoleId = Role::where('name', 'employee')->value('id');
            if ($employeeRoleId) {
                $this->roles()->syncWithoutDetaching([$employeeRoleId => ['locked' => true]]);

                // Role above was added suspended (unlocked), so nothing became
                // active — fall back to employee rather than leaving it null.
                if (!$this->active_role_id) {
                    $this->forceFill(['active_role_id' => $employeeRoleId])->save();
                }
            }
        }
    }

    // Every user is an employee, so every user needs a profile + employee
    // record. Idempotent — only fills in what's missing, so it's safe to call
    // after a creation path has already written its own (richer) profile data.
    public function ensureEmployeeProfile(): void {
        $profile = $this->profile()->first() ?? $this->profile()->create([
            'first_name'           => $this->username,
            'last_name'            => '',
            'length_of_service'    => '',
            'education_attainment' => ['data' => []],
        ]);

        if (!DB::table('employee_profiles')->where('profile_id', $profile->id)->exists()) {
            // Raw insert: employee_profiles has no id/timestamps, and
            // work_specification isn't fillable on the model.
            DB::table('employee_profiles')->insert([
                'profile_id'         => $profile->id,
                'position'           => $this->defaultPosition(),
                'status'             => 'permanent',
                'work_specification' => json_encode(['data' => []]),
            ]);
        }

        $this->unsetRelation('profile');
    }

    private function defaultPosition(): string {
        $labels = [
            'super_admin'         => 'System Administrator',
            'sub_admin'           => 'Sub Administrator',
            'regional_director'   => 'Regional Director',
            'provincial_admin'    => 'Provincial Administrator',
            'provincial_director' => 'Provincial Director',
        ];
        $admin = $this->roles()->whereIn('roles.name', Role::ADMIN_ROLES)->value('roles.name');

        return $labels[$admin] ?? 'Staff';
    }

    // One active provincial director per province, one active regional
    // director per region. "Active" = account activated AND the director role
    // locked — so a deactivated or suspended director doesn't hold the slot.
    // Returns the other director already holding the slot, if any.
    // $provinceId lets a caller check a province the user is *about* to move to.
    public function conflictingDirector(string $roleName, ?int $provinceId = null): ?User {
        $query = User::whereKeyNot($this->id)->whereRole($roleName)->where('activate', true);

        if ($roleName === 'provincial_director') {
            $provinceId ??= $this->provinces()->value('provinces.id');
            return $provinceId ? $query->whereProvince($provinceId)->first() : null;
        }

        if ($roleName === 'regional_director') {
            return $this->region_id ? $query->where('region_id', $this->region_id)->first() : null;
        }

        return null;
    }

    // Throws if making $roleName active for this user would give its province
    // or region a second active director.
    public function assertDirectorSlotFree(string $roleName, ?int $provinceId = null): void {
        $other = $this->conflictingDirector($roleName, $provinceId);
        if (!$other) return;

        $other->loadMissing('profile');
        $name  = trim(($other->profile?->first_name ?? '') . ' ' . ($other->profile?->last_name ?? '')) ?: $other->username;
        $scope = $roleName === 'provincial_director' ? 'This province' : 'This region';
        $label = $roleName === 'provincial_director' ? 'provincial director' : 'regional director';

        throw ValidationException::withMessages([
            'role' => "{$scope} already has an active {$label} ({$name}). Deactivate them or suspend their role first.",
        ]);
    }

    // Director roles this user holds that are currently locked (in effect).
    public function lockedDirectorRoles(): array {
        return $this->lockedRoles()->pluck('name')
            ->intersect(['provincial_director', 'regional_director'])
            ->values()->all();
    }

    // The single place a role gets detached from a user — mirrors assignRole().
    // 'employee' is permanent: every account is an employee first, so it can
    // never be removed (only an admin-tier role sitting on top of it can be).
    public function removeRole(string $name): void {
        if ($name === 'employee') {
            throw ValidationException::withMessages([
                'role' => 'The employee role is permanent and cannot be removed.',
            ]);
        }

        if ($this->roles()->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => 'A user must keep at least one role.',
            ]);
        }

        $role = $this->roles()->where('roles.name', $name)->firstOrFail();
        $this->roles()->detach($role->id);

        if ($this->active_role_id === $role->id) {
            $stillLocked = $this->fresh()->lockedRoles();
            $this->forceFill([
                'active_role_id' => $stillLocked->count() === 1 ? $stillLocked->first()->id : null,
            ])->save();
        }
    }
}
