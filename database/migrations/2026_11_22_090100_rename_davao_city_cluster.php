<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rename the Region XI city cluster from "Davao City" to "Davao City Center".
 *
 * `provinces.name` doubles as a lookup key, so the same string is mirrored in
 * Province::REGIONS, resources/js/Data/provinceGeography.js, CSTC_REGION_CODE in
 * PhilippinesMap.vue, the `cstc` property in public/geo/cstc-cities.geojson, and
 * the CSTC seeders - all updated alongside this migration. The geojson feature's
 * own `name` stays "Davao City": that is the city polygon, not the cluster.
 */
return new class extends Migration
{
    private const OLD = 'Davao City';
    private const NEW = 'Davao City Center';

    public function up(): void
    {
        DB::table('provinces')->where('name', self::OLD)->update(['name' => self::NEW]);
    }

    public function down(): void
    {
        DB::table('provinces')->where('name', self::NEW)->update(['name' => self::OLD]);
    }
};
