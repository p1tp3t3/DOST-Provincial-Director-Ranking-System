<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Every account is an employee first — an admin-tier role (super_admin,
// sub_admin, regional_director, provincial_admin, provincial_director) sits
// on top of that, it never replaces it. This grants the locked 'employee'
// role to every user who doesn't already hold it (their active_role_id is
// left untouched — only the set of roles they hold changes).
return new class extends Migration
{
    public function up(): void
    {
        $employeeRoleId = DB::table('roles')->where('name', 'employee')->value('id');
        if (!$employeeRoleId) return;

        $now = now();

        $missingIds = DB::table('users')
            ->whereNotIn('id', DB::table('user_roles')->where('role_id', $employeeRoleId)->pluck('user_id'))
            ->pluck('id');

        foreach ($missingIds->chunk(500) as $chunk) {
            DB::table('user_roles')->insert($chunk->map(fn ($userId) => [
                'user_id'    => $userId,
                'role_id'    => $employeeRoleId,
                'locked'     => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }
    }

    public function down(): void
    {
        // Irreversible data migration — matches this project's convention
        // for the earlier role-removal migrations.
    }
};
