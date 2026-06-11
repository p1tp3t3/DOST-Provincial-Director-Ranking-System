<?php

use App\Models\Province;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfills and re-applies the canonical province → region map (Province::REGIONS).
 *
 * Two things this fixes on existing databases:
 *  1. The 77 non-CSTC provinces were left with region = NULL, because the original
 *     add_region migration ran before the seeder created them.
 *  2. The region scheme itself changed: the Negros Island Region (NIR) now groups
 *     Negros Occidental/Oriental + Siquijor, Sulu moves from BARMM into Region IX,
 *     and BARMM is dropped (no DOST PSTD provinces remain there).
 *
 * Idempotent: it simply sets each province's region to its canonical value by name.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (Province::REGIONS as $name => $region) {
            DB::table('provinces')->where('name', $name)->update(['region' => $region]);
        }
    }

    public function down(): void
    {
        // Restore the previous scheme for the provinces this migration moved.
        $previous = [
            'Negros Occidental' => 'Region VI',
            'Negros Oriental'   => 'Region VII',
            'Siquijor'          => 'Region VII',
            'Sulu'              => 'BARMM',
        ];
        foreach ($previous as $name => $region) {
            DB::table('provinces')->where('name', $name)->update(['region' => $region]);
        }
    }
};
