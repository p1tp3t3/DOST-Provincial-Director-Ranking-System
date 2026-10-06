<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The provincial_sub_admin role is retired — it was never actually assigned
// to any account (no seeder, no nav entry), so this only needs to narrow the
// enum, with no user rows to clean up first.
//
// Guarded on Schema::hasColumn: a fresh install's base migration no longer
// creates users.role at all — nothing to narrow on that path.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'role')) {
            return;
        }

        // SQLite stores ENUMs as text and doesn't support MODIFY COLUMN; only run on MySQL/MariaDB
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
                'super_admin','sub_admin','regional_director','provincial_admin',
                'provincial_director','employee'
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
                'super_admin','sub_admin','regional_director','provincial_admin',
                'provincial_sub_admin','provincial_director','employee'
            ) NOT NULL");
        }
    }
};
