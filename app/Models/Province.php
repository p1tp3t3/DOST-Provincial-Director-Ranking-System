<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    public $table    = 'provinces';
    protected $fillable = ['name', 'category', 'region_id', 'num_plantilla_employees', 'num_municipalities', 'num_cities'];

    /**
     * Canonical province → DOST region assignment. Single source of truth shared by
     * the seeders for resolving each province's region_id. Reflects the current
     * 17-region scheme: the Negros Island Region (NIR) groups Negros
     * Occidental/Oriental + Siquijor, Sulu sits under Region IX, and BARMM is not
     * used (no DOST PSTD provinces).
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
        // Region I - Ilocos
        'Ilocos Norte'          => 'Region I',
        'Ilocos Sur'            => 'Region I',
        'La Union'              => 'Region I',
        'Pangasinan'            => 'Region I',
        // Region II - Cagayan Valley
        'Batanes'               => 'Region II',
        'Cagayan'               => 'Region II',
        'Isabela'               => 'Region II',
        'Nueva Vizcaya'         => 'Region II',
        'Quirino'               => 'Region II',
        // Region III - Central Luzon
        'Aurora'                => 'Region III',
        'Bataan'                => 'Region III',
        'Bulacan'               => 'Region III',
        'Nueva Ecija'           => 'Region III',
        'Pampanga'              => 'Region III',
        'Tarlac'                => 'Region III',
        'Zambales'              => 'Region III',
        // Region IV-A - CALABARZON
        'Batangas'              => 'Region IV-A',
        'Cavite'                => 'Region IV-A',
        'Laguna'                => 'Region IV-A',
        'Quezon'                => 'Region IV-A',
        'Rizal'                 => 'Region IV-A',
        // Region IV-B - MIMAROPA
        'Marinduque'            => 'Region IV-B',
        'Occidental Mindoro'    => 'Region IV-B',
        'Oriental Mindoro'      => 'Region IV-B',
        'Palawan'               => 'Region IV-B',
        'Romblon'               => 'Region IV-B',
        // Region V - Bicol
        'Albay'                 => 'Region V',
        'Camarines Norte'       => 'Region V',
        'Camarines Sur'         => 'Region V',
        'Catanduanes'           => 'Region V',
        'Masbate'               => 'Region V',
        'Sorsogon'              => 'Region V',
        // Region VI - Western Visayas
        'Aklan'                 => 'Region VI',
        'Antique'               => 'Region VI',
        'Capiz'                 => 'Region VI',
        'Guimaras'              => 'Region VI',
        'Iloilo'                => 'Region VI',
        // Region VII - Central Visayas
        'Bohol'                 => 'Region VII',
        'Cebu Province'         => 'Region VII',
        // Region VIII - Eastern Visayas
        'Biliran'               => 'Region VIII',
        'Eastern Samar'         => 'Region VIII',
        'Leyte'                 => 'Region VIII',
        'Northern Samar'        => 'Region VIII',
        'Samar (Western Samar)' => 'Region VIII',
        'Southern Leyte'        => 'Region VIII',
        // NIR - Negros Island Region
        'Negros Occidental'     => 'NIR',
        'Negros Oriental'       => 'NIR',
        'Siquijor'              => 'NIR',
        // Region IX - Zamboanga Peninsula
        'Zamboanga del Norte'   => 'Region IX',
        'Zamboanga del Sur'     => 'Region IX',
        'Zamboanga Sibugay'     => 'Region IX',
        'ZCIC'                  => 'Region IX',
        'Sulu'                  => 'Region IX',
        // Region X - Northern Mindanao
        'Bukidnon'              => 'Region X',
        'Camiguin'              => 'Region X',
        'Lanao del Norte'       => 'Region X',
        'Misamis Occidental'    => 'Region X',
        'Misamis Oriental'      => 'Region X',
        // Region XI - Davao
        'Davao de Oro'          => 'Region XI',
        'Davao del Norte'       => 'Region XI',
        'Davao del Sur'         => 'Region XI',
        'Davao Occidental'      => 'Region XI',
        'Davao Oriental'        => 'Region XI',
        'Davao City'            => 'Region XI',
        // Region XII - SOCCSKSARGEN
        'Cotabato (North)'      => 'Region XII',
        'Sarangani'             => 'Region XII',
        'South Cotabato'        => 'Region XII',
        'Sultan Kudarat'        => 'Region XII',
        // Region XIII - Caraga
        'Agusan del Norte'      => 'Region XIII',
        'Agusan del Sur'        => 'Region XIII',
        'Dinagat Island'        => 'Region XIII',
        'Surigao del Norte'     => 'Region XIII',
        'Surigao del Sur'       => 'Region XIII',
    ];

    /**
     * Region → island grouping, used to populate the `region` table's island_under
     * column when seeding. Mirrors REGION_TO_ISLAND in PhilippinesMap.vue.
     */
    public const REGION_ISLANDS = [
        'NCR'         => 'luzon',
        'CAR'         => 'luzon',
        'Region I'    => 'luzon',
        'Region II'   => 'luzon',
        'Region III'  => 'luzon',
        'Region IV-A' => 'luzon',
        'Region IV-B' => 'luzon',
        'Region V'    => 'luzon',
        'Region VI'   => 'visayas',
        'Region VII'  => 'visayas',
        'Region VIII' => 'visayas',
        'NIR'         => 'visayas',
        'Region IX'   => 'mindanao',
        'Region X'    => 'mindanao',
        'Region XI'   => 'mindanao',
        'Region XII'  => 'mindanao',
        'Region XIII' => 'mindanao',
    ];

    public function region() {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }

    public function users() {
        return $this->belongsToMany(User::class, 'user_province', 'province_id', 'user_id')
                     ->using(UserProvince::class);
    }

    public function directorAssignments() {
        return $this->users()->where('role', 'provincial_director');
    }

    public function provincialKpis() {
        return $this->hasMany(ProvincialKPI::class, 'province_id');
    }

    public function getProvincialDirectorAttribute() {
        return $this->directorAssignments->first();
    }
}
