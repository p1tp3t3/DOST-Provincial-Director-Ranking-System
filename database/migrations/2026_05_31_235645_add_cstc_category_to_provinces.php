<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend the ENUM to include 'cstc'. The 6 named CSTC "provinces" are
        // created by DatabaseSeeder (with their factual region_id), since the
        // provinces.region_id FK requires the region table to be seeded first.
        DB::statement("ALTER TABLE provinces MODIFY COLUMN category ENUM('micro','small','medium','large','cstc') NOT NULL");
    }

    public function down(): void
    {
        DB::table('provinces')->whereIn('name', ['CAMANAVA','PAMAMAZON','PAMAMARISAN','MUNTAPARLAS','ZCIC','Davao City'])->delete();
        DB::statement("ALTER TABLE provinces MODIFY COLUMN category ENUM('micro','small','medium','large') NOT NULL");
    }
};
