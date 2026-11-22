<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Show the Region IX city cluster by its full name instead of the "ZCIC" acronym.
 *
 * `provinces.name` doubles as a lookup key, so the same string is mirrored in
 * Province::REGIONS, resources/js/Data/provinceGeography.js, CSTC_REGION_CODE in
 * PhilippinesMap.vue, the `cstc` property in public/geo/cstc-cities.geojson, and
 * the CSTC seeders - all updated alongside this migration.
 */
return new class extends Migration
{
    private const OLD = 'ZCIC';
    private const NEW = 'Zamboanga City - Isabela City';

    public function up(): void
    {
        DB::table('provinces')->where('name', self::OLD)->update(['name' => self::NEW]);
    }

    public function down(): void
    {
        DB::table('provinces')->where('name', self::NEW)->update(['name' => self::OLD]);
    }
};
