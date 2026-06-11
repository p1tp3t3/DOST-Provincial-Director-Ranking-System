<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite stores ENUMs as text and doesn't support MODIFY COLUMN; only run on MySQL/MariaDB
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE provinces MODIFY COLUMN category ENUM('micro','small','medium','large','cstc') NOT NULL");
        }

        // Insert the 6 named CSTCs from the ranking matrix Excel
        $cstcs = [
            ['name' => 'CAMANAVA',    'num_plantilla_employees' => 0, 'num_municipalities' => 0, 'num_cities' => 4],
            ['name' => 'PAMAMAZON',   'num_plantilla_employees' => 0, 'num_municipalities' => 0, 'num_cities' => 0],
            ['name' => 'PAMAMARISAN', 'num_plantilla_employees' => 4, 'num_municipalities' => 0, 'num_cities' => 0],
            ['name' => 'MUNTAPARLAS', 'num_plantilla_employees' => 4, 'num_municipalities' => 0, 'num_cities' => 3],
            ['name' => 'ZCIC',        'num_plantilla_employees' => 1, 'num_municipalities' => 0, 'num_cities' => 1],
            ['name' => 'Davao City',  'num_plantilla_employees' => 3, 'num_municipalities' => 0, 'num_cities' => 1],
        ];

        foreach ($cstcs as $cstc) {
            DB::table('provinces')->insertOrIgnore([
                'name'                    => $cstc['name'],
                'category'                => 'cstc',
                'num_plantilla_employees' => $cstc['num_plantilla_employees'],
                'num_municipalities'      => $cstc['num_municipalities'],
                'num_cities'              => $cstc['num_cities'],
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('provinces')->whereIn('name', ['CAMANAVA','PAMAMAZON','PAMAMARISAN','MUNTAPARLAS','ZCIC','Davao City'])->delete();
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE provinces MODIFY COLUMN category ENUM('micro','small','medium','large') NOT NULL");
        }
    }
};
