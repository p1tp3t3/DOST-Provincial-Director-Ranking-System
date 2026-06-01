<?php

namespace Database\Seeders;

use App\Helpers\CSVToDFHelper;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KPIScoreSeeder extends Seeder
{
    public function run(): void
    {
        // Build province name → director_id lookup
        $directorMap = [];
        foreach (Province::with('provincialDirector')->get() as $province) {
            if ($province->provincialDirector) {
                $directorMap[$province->name] = $province->provincialDirector->id;
            }
        }

        $rows   = CSVToDFHelper::get_df('kpi-scores.csv');
        $batch  = [];
        $missed = [];

        foreach ($rows as $row) {
            $directorId = $directorMap[$row['province']] ?? null;
            if (!$directorId) {
                $missed[$row['province']] = true;
                continue;
            }
            $batch[] = [
                'provincial_director_id' => $directorId,
                'kpi_id'                 => (int) $row['kpi_id'],
                'kpi_subrow_id'          => (int) $row['subrow_id'],
                'year'                   => (int) $row['year'],
                'target'                 => $row['target']       !== '' ? $row['target']       : null,
                'accomplished'           => $row['accomplished'] !== '' ? $row['accomplished'] : null,
            ];
        }

        foreach (array_chunk($batch, 500) as $chunk) {
            DB::table('provincial_director_kpis')->insert($chunk);
        }

        if ($missed) {
            $this->command->warn('No director found for provinces: ' . implode(', ', array_keys($missed)));
        }

        $this->command->info('KPI scores inserted: ' . count($batch));
    }
}
