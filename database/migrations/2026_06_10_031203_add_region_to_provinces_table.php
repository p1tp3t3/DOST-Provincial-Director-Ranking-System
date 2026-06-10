<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->string('region')->nullable()->after('category');
        });

        $map = [
            // CSTC
            'CAMANAVA'              => 'NCR',
            'PAMAMAZON'             => 'NCR',
            'PAMAMARISAN'           => 'NCR',
            'MUNTAPARLAS'           => 'NCR',
            'ZCIC'                  => 'Region IX',
            'Davao City'            => 'Region XI',
            // Micro
            'Agusan del Norte'      => 'Region XIII',
            'Apayao'                => 'CAR',
            'Aurora'                => 'Region III',
            'Batanes'               => 'Region II',
            'Biliran'               => 'Region VIII',
            'Camiguin'              => 'Region X',
            'Davao del Norte'       => 'Region XI',
            'Davao del Sur'         => 'Region XI',
            'Davao Occidental'      => 'Region XI',
            'Davao Oriental'        => 'Region XI',
            'Dinagat Island'        => 'Region XIII',
            'Guimaras'              => 'Region VI',
            'Kalinga'               => 'CAR',
            'Marinduque'            => 'Region IV-B',
            'Mountain Province'     => 'CAR',
            'Quirino'               => 'Region II',
            'Sarangani'             => 'Region XII',
            'Siquijor'              => 'Region VII',
            // Small
            'Agusan del Sur'        => 'Region XIII',
            'Aklan'                 => 'Region VI',
            'Albay'                 => 'Region V',
            'Antique'               => 'Region VI',
            'Bataan'                => 'Region III',
            'Benguet'               => 'CAR',
            'Bukidnon'              => 'Region X',
            'Bulacan'               => 'Region III',
            'Camarines Norte'       => 'Region V',
            'Capiz'                 => 'Region VI',
            'Catanduanes'           => 'Region V',
            'Cotabato (North)'      => 'Region XII',
            'Davao de Oro'          => 'Region XI',
            'Ifugao'                => 'CAR',
            'La Union'              => 'Region I',
            'Masbate'               => 'Region V',
            'Misamis Occidental'    => 'Region X',
            'Negros Oriental'       => 'Region VII',
            'Nueva Vizcaya'         => 'Region II',
            'Occidental Mindoro'    => 'Region IV-B',
            'Oriental Mindoro'      => 'Region IV-B',
            'Pampanga'              => 'Region III',
            'Rizal'                 => 'Region IV-A',
            'Romblon'               => 'Region IV-B',
            'Sorsogon'              => 'Region V',
            'South Cotabato'        => 'Region XII',
            'Southern Leyte'        => 'Region VIII',
            'Sultan Kudarat'        => 'Region XII',
            'Sulu'                  => 'BARMM',
            'Surigao del Norte'     => 'Region XIII',
            'Surigao del Sur'       => 'Region XIII',
            'Tarlac'                => 'Region III',
            'Zambales'              => 'Region III',
            'Zamboanga Sibugay'     => 'Region IX',
            // Medium
            'Abra'                  => 'CAR',
            'Batangas'              => 'Region IV-A',
            'Cagayan'               => 'Region II',
            'Cavite'                => 'Region IV-A',
            'Eastern Samar'         => 'Region VIII',
            'Ilocos Norte'          => 'Region I',
            'Laguna'                => 'Region IV-A',
            'Lanao del Norte'       => 'Region X',
            'Misamis Oriental'      => 'Region X',
            'Northern Samar'        => 'Region VIII',
            'Nueva Ecija'           => 'Region III',
            'Palawan'               => 'Region IV-B',
            'Samar (Western Samar)' => 'Region VIII',
            'Zamboanga del Norte'   => 'Region IX',
            'Zamboanga del Sur'     => 'Region IX',
            // Large
            'Bohol'                 => 'Region VII',
            'Camarines Sur'         => 'Region V',
            'Cebu Province'         => 'Region VII',
            'Ilocos Sur'            => 'Region I',
            'Iloilo'                => 'Region VI',
            'Isabela'               => 'Region II',
            'Leyte'                 => 'Region VIII',
            'Negros Occidental'     => 'Region VI',
            'Pangasinan'            => 'Region I',
            'Quezon'                => 'Region IV-A',
        ];

        foreach ($map as $name => $region) {
            DB::table('provinces')->where('name', $name)->update(['region' => $region]);
        }
    }

    public function down(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropColumn('region');
        });
    }
};
