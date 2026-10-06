<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The regional_admin role is retired — regional_director is now the sole
// region-level role, inheriting every capability regional_admin had. This
// deletes the existing regional_admin accounts (and their dependent rows,
// since several FKs on users don't cascade) and narrows the role enum.
//
// Guarded on Schema::hasColumn: a fresh install's base migration no longer
// creates users.role at all (roles moved to the roles/user_roles tables), so
// on that path this migration — which still runs chronologically before the
// role column removal — has nothing to do.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            return;
        }

        $ids = DB::table('users')->where('role', 'regional_admin')->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('activity_logs')->whereIn('user_id', $ids)->delete();
            DB::table('notifications')->whereIn('sender_id', $ids)->orWhereIn('receiver_id', $ids)->delete();
            DB::table('profiles')->whereIn('user_id', $ids)->delete();
            DB::table('users')->whereIn('id', $ids)->delete();
        }

        // SQLite stores ENUMs as text and doesn't support MODIFY COLUMN; only run on MySQL/MariaDB
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
                'super_admin','sub_admin','regional_director','provincial_admin',
                'provincial_sub_admin','provincial_director','employee'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
                'super_admin','sub_admin','regional_admin','regional_director','provincial_admin',
                'provincial_sub_admin','provincial_director','employee'
            ) NOT NULL");
        }
        // Deleted regional_admin accounts are not restored — re-seed if needed.
    }
};
