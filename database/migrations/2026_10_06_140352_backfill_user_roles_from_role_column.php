<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// One-time data migration: converts every existing users.role value into a
// locked user_roles row and sets active_role_id to match, before the role
// column is dropped. No-op on a fresh install (base migration never creates
// the role column, so there's nothing to backfill).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            return;
        }

        $roleIds = DB::table('roles')->pluck('id', 'name');

        DB::table('users')->select('id', 'role', 'created_at')->orderBy('id')
            ->each(function ($user) use ($roleIds) {
                if (!isset($roleIds[$user->role])) return;

                $roleId = $roleIds[$user->role];

                DB::table('user_roles')->insert([
                    'user_id'    => $user->id,
                    'role_id'    => $roleId,
                    'locked'     => true,
                    'created_at' => $user->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                DB::table('users')->where('id', $user->id)->update(['active_role_id' => $roleId]);
            });
    }

    public function down(): void
    {
        // Irreversible data migration — matches this project's existing
        // convention for the earlier role-removal migrations.
    }
};
