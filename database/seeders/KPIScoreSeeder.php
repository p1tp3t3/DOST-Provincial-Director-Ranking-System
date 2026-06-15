<?php

namespace Database\Seeders;

use App\Models\KPI;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KPIScoreSeeder extends Seeder
{
    // Maps OLD subrow IDs from the legacy 54-indicator layout (kpi-scores.csv) to the
    // NEW PSTD Ranking Matrix KPI codes. Old subrows not listed here have no matrix
    // counterpart (e.g. "% increase in productivity", "Number of beneficiaries", and
    // the implemented/ongoing/completed stages for SETUP/GIA/CEST) and are skipped.
    //
    // Subrow 33 (ongoing SETUP) and 35 (delinquent SETUP) are kept as supporting input
    // rows — the system derives the % Delinquent SETUP KPI from them at scoring time.
    public const SUBROW_TO_KPI_CODE = [
        // CORE
        1  => 'core_rd_proposals_funded',
        2  => 'core_collab_rd_implemented',
        3  => 'core_dost_tech_transferred',
        5  => 'core_dost_tech_adopted',
        6  => 'core_tech_adoptors',
        10 => 'core_st_interventions',
        11 => 'core_startups_assisted',
        12 => 'core_customers_assisted',
        13 => 'core_msmes_assisted',
        14 => 'core_employment_generated',
        15 => 'core_gross_sales_generated',
        17 => 'core_firms_graduated',
        18 => 'core_firms_exporters',
        19 => 'core_innovation_hubs',
        20 => 'core_communities_assisted',
        21 => 'core_tech_deployed_communities',
        30 => 'core_setup_projects_funded',
        31 => 'core_setup_value_funded',
        36 => 'core_setup_refund_rate',
        37 => 'core_setup_refunded_amount',
        39 => 'core_gia_proposals_funded',
        40 => 'core_gia_value_funded',
        44 => 'core_cest_proposals_funded',
        45 => 'core_cest_value_funded',
        33 => 'input_setup_ongoing_count',
        35 => 'input_setup_delinquent_count',
        // FUNCTIONAL
        25 => 'func_drr_measures',
        26 => 'func_drrm_collaborations',
        53 => 'func_linkages_established',
        54 => 'func_external_funds_sourced',
        // SUPPORT
        9  => 'supp_st_promo_activities',
        23 => 'supp_samples_referred',
        24 => 'supp_testing_calibration_referred',
        27 => 'supp_pct_programs_risk_analysis',
        28 => 'supp_work_process_innovations',
        38 => 'supp_customer_satisfaction',
        50 => 'supp_facebook_posts',
        49 => 'supp_press_releases',
    ];

    public function run(): void
    {
        $directorMap = [];
        foreach (Province::with('provincialDirector')->get() as $province) {
            if ($province->provincialDirector) {
                $directorMap[$province->name] = $province->provincialDirector->id;
            }
        }

        $kpiIdByCode = KPI::pluck('id', 'code')->toArray();

        $rows        = require __DIR__ . '/data/kpi-scores.php';
        $batch       = [];
        $missedProv  = [];
        $skippedSub  = [];

        foreach ($rows as $row) {
            $directorId = $directorMap[$row['province']] ?? null;
            if (!$directorId) {
                $missedProv[$row['province']] = true;
                continue;
            }

            $subrowId = (int) $row['subrow_id'];
            $code     = self::SUBROW_TO_KPI_CODE[$subrowId] ?? null;
            if ($code === null) {
                $skippedSub[$subrowId] = true;
                continue;
            }

            $kpiId = $kpiIdByCode[$code] ?? null;
            if ($kpiId === null) continue;

            $batch[] = [
                'provincial_director_id' => $directorId,
                'kpi_id'                 => $kpiId,
                'year'                   => (int) $row['year'],
                'target'                 => $row['target']       !== '' ? $row['target']       : null,
                'accomplished'           => $row['accomplished'] !== '' ? $row['accomplished'] : null,
            ];
        }

        foreach (array_chunk($batch, 500) as $chunk) {
            DB::table('provincial_director_kpis')->insert($chunk);
        }

        if ($missedProv) {
            $this->command->warn('No director found for provinces: ' . implode(', ', array_keys($missedProv)));
        }
        if ($skippedSub) {
            $this->command->info('Skipped unmapped legacy subrow IDs: ' . implode(', ', array_keys($skippedSub)));
        }
        $this->command->info('KPI scores inserted: ' . count($batch));
    }
}
