<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    public $table    = 'provinces';
    protected $fillable = ['name', 'category', 'region', 'num_plantilla_employees', 'num_municipalities', 'num_cities'];

    /**
     * Canonical province → DOST region assignment. Single source of truth shared by
     * the region migration and the seeders. Reflects the current 17-region scheme:
     * the Negros Island Region (NIR) groups Negros Occidental/Oriental + Siquijor,
     * Sulu sits under Region IX, and BARMM is not used (no DOST PSTD provinces).
     */
    public const REGIONS = [
        // NCR (CSTC clusters)
        'CAMANAVA'              => 'NCR',
        'PAMAMAZON'             => 'NCR',
        'PAMAMARISAN'           => 'NCR',
        'MUNTAPARLAS'           => 'NCR',
        // CAR
        'Abra'                  => 'CAR',
        'Apayao'                => 'CAR',
        'Benguet'               => 'CAR',
        'Ifugao'                => 'CAR',
        'Kalinga'               => 'CAR',
        'Mountain Province'     => 'CAR',
        // Region I — Ilocos
        'Ilocos Norte'          => 'Region I',
        'Ilocos Sur'            => 'Region I',
        'La Union'              => 'Region I',
        'Pangasinan'            => 'Region I',
        // Region II — Cagayan Valley
        'Batanes'               => 'Region II',
        'Cagayan'               => 'Region II',
        'Isabela'               => 'Region II',
        'Nueva Vizcaya'         => 'Region II',
        'Quirino'               => 'Region II',
        // Region III — Central Luzon
        'Aurora'                => 'Region III',
        'Bataan'                => 'Region III',
        'Bulacan'               => 'Region III',
        'Nueva Ecija'           => 'Region III',
        'Pampanga'              => 'Region III',
        'Tarlac'                => 'Region III',
        'Zambales'              => 'Region III',
        // Region IV-A — CALABARZON
        'Batangas'              => 'Region IV-A',
        'Cavite'                => 'Region IV-A',
        'Laguna'                => 'Region IV-A',
        'Quezon'                => 'Region IV-A',
        'Rizal'                 => 'Region IV-A',
        // Region IV-B — MIMAROPA
        'Marinduque'            => 'Region IV-B',
        'Occidental Mindoro'    => 'Region IV-B',
        'Oriental Mindoro'      => 'Region IV-B',
        'Palawan'               => 'Region IV-B',
        'Romblon'               => 'Region IV-B',
        // Region V — Bicol
        'Albay'                 => 'Region V',
        'Camarines Norte'       => 'Region V',
        'Camarines Sur'         => 'Region V',
        'Catanduanes'           => 'Region V',
        'Masbate'               => 'Region V',
        'Sorsogon'              => 'Region V',
        // Region VI — Western Visayas
        'Aklan'                 => 'Region VI',
        'Antique'               => 'Region VI',
        'Capiz'                 => 'Region VI',
        'Guimaras'              => 'Region VI',
        'Iloilo'                => 'Region VI',
        // Region VII — Central Visayas
        'Bohol'                 => 'Region VII',
        'Cebu Province'         => 'Region VII',
        // Region VIII — Eastern Visayas
        'Biliran'               => 'Region VIII',
        'Eastern Samar'         => 'Region VIII',
        'Leyte'                 => 'Region VIII',
        'Northern Samar'        => 'Region VIII',
        'Samar (Western Samar)' => 'Region VIII',
        'Southern Leyte'        => 'Region VIII',
        // NIR — Negros Island Region
        'Negros Occidental'     => 'NIR',
        'Negros Oriental'       => 'NIR',
        'Siquijor'              => 'NIR',
        // Region IX — Zamboanga Peninsula
        'Zamboanga del Norte'   => 'Region IX',
        'Zamboanga del Sur'     => 'Region IX',
        'Zamboanga Sibugay'     => 'Region IX',
        'ZCIC'                  => 'Region IX',
        'Sulu'                  => 'Region IX',
        // Region X — Northern Mindanao
        'Bukidnon'              => 'Region X',
        'Camiguin'              => 'Region X',
        'Lanao del Norte'       => 'Region X',
        'Misamis Occidental'    => 'Region X',
        'Misamis Oriental'      => 'Region X',
        // Region XI — Davao
        'Davao de Oro'          => 'Region XI',
        'Davao del Norte'       => 'Region XI',
        'Davao del Sur'         => 'Region XI',
        'Davao Occidental'      => 'Region XI',
        'Davao Oriental'        => 'Region XI',
        'Davao City'            => 'Region XI',
        // Region XII — SOCCSKSARGEN
        'Cotabato (North)'      => 'Region XII',
        'Sarangani'             => 'Region XII',
        'South Cotabato'        => 'Region XII',
        'Sultan Kudarat'        => 'Region XII',
        // Region XIII — Caraga
        'Agusan del Norte'      => 'Region XIII',
        'Agusan del Sur'        => 'Region XIII',
        'Dinagat Island'        => 'Region XIII',
        'Surigao del Norte'     => 'Region XIII',
        'Surigao del Sur'       => 'Region XIII',
    ];

    public function user() {
        return $this->hasMany(User::class, 'province_id', 'id');
    }
    public function provincialDirector() {
        return $this->hasOne(User::class, 'province_id', 'id')
                    ->where('role', 'provincial_director');
    }
}
