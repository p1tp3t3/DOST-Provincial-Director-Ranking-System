<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Every user is an employee, so every user needs a profile + employee record.
// Admin-tier accounts were previously created without one (or with a profile
// but no employee_profiles row). This fills in only what's missing; existing
// profile data is never touched. Mirrors User::ensureEmployeeProfile().
return new class extends Migration
{
    private const POSITIONS = [
        'super_admin'         => 'System Administrator',
        'sub_admin'           => 'Sub Administrator',
        'regional_director'   => 'Regional Director',
        'provincial_admin'    => 'Provincial Administrator',
        'provincial_director' => 'Provincial Director',
    ];

    public function up(): void
    {
        // Each user's admin-tier role (if any) — at most one per user.
        $adminRole = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereIn('roles.name', array_keys(self::POSITIONS))
            ->pluck('roles.name', 'user_roles.user_id');

        $users = DB::table('users')->select('id', 'username')->orderBy('id')->get();

        foreach ($users as $user) {
            $role = $adminRole[$user->id] ?? null;

            $profileId = DB::table('profiles')->where('user_id', $user->id)->value('id');

            if (!$profileId) {
                $provinceName = DB::table('user_province')
                    ->join('provinces', 'provinces.id', '=', 'user_province.province_id')
                    ->where('user_province.user_id', $user->id)
                    ->value('provinces.name');

                [$first, $last] = $role === 'provincial_admin' && $provinceName
                    ? ['Provincial Admin', $provinceName]
                    : [$user->username, ''];

                $profileId = DB::table('profiles')->insertGetId([
                    'user_id'              => $user->id,
                    'first_name'           => $first,
                    'middle_name'          => '',
                    'last_name'            => $last,
                    'length_of_service'    => '',
                    'education_attainment' => json_encode(['data' => []]),
                ]);
            }

            if (!DB::table('employee_profiles')->where('profile_id', $profileId)->exists()) {
                DB::table('employee_profiles')->insert([
                    'profile_id'         => $profileId,
                    'position'           => self::POSITIONS[$role] ?? 'Staff',
                    'status'             => 'permanent',
                    'work_specification' => json_encode(['data' => []]),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Irreversible data migration — matches this project's convention.
    }
};
