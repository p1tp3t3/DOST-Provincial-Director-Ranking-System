<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Merges the CSTC classification tier into the Large tier. The six CSTC city
 * clusters (CAMANAVA, PAMAMAZON, PAMAMARISAN, MUNTAPARLAS, ZCIC, Davao City)
 * are reclassified as Large and ranked within that tier; 'cstc' is removed from
 * the provinces.category enum.
 */
return new class extends Migration
{
    private const CLUSTERS = ['CAMANAVA', 'PAMAMAZON', 'PAMAMARISAN', 'MUNTAPARLAS', 'ZCIC', 'Davao City'];

    public function up(): void
    {
        DB::table('provinces')->where('category', 'cstc')->update(['category' => 'large']);

        // SQLite stores enums as text and can't MODIFY COLUMN; only constrain on MySQL/MariaDB.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE provinces MODIFY COLUMN category ENUM('micro','small','medium','large') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE provinces MODIFY COLUMN category ENUM('micro','small','medium','large','cstc') NOT NULL");
        }

        DB::table('provinces')->whereIn('name', self::CLUSTERS)->update(['category' => 'cstc']);
    }
};
