<?php

namespace Database\Seeders;

use App\Models\KPI;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Backfills the linkages table with dummy MOA/MOU records that match each
// director's previously-typed "accomplished" value for `func_linkages_established`.
// Used so the new evidence-based module has plausible data on a fresh seed.
//
// Source-data caveat: some legacy `accomplished` values for this KPI are actually
// peso amounts (e.g. 6,158,927.5) rather than counts, so we cap the per-row
// generated count at MAX_PER_ROW. Anything above the cap is clamped and logged.
class LinkageSeeder extends Seeder
{
    private const MAX_PER_ROW    = 30;
    private const FLUSH_EVERY    = 500;

    private const PARTNERS = [
        'DepEd Provincial Office',
        'DPWH Regional Office',
        'DTI Provincial Office',
        'DA Regional Office',
        'Local Government Unit',
        'Provincial Chamber of Commerce',
        'State University',
        'Local Polytechnic Institute',
        'PNP Provincial Command',
        'Provincial Health Office',
        'TESDA Provincial Office',
        'Department of Tourism',
        'NEDA Regional Office',
        'Rural Bank Association',
        'Cooperative Development Authority',
    ];

    private const TITLE_TEMPLATES = [
        'Joint Implementation of S&T Scholarship Program',
        'Technology Transfer Partnership',
        'Innovation Hub Collaboration',
        'Capacity Building and Training Program',
        'MSME Development Initiative',
        'Community Empowerment through S&T',
        'Disaster Risk Reduction Cooperation',
        'Research and Development Collaboration',
        'Skills Development Program',
        'Industry Linkage Agreement',
    ];

    public function run(): void
    {
        $kpi = KPI::where('code', 'func_linkages_established')->first();
        if (!$kpi) {
            $this->command->warn('KPI func_linkages_established not found — skipping.');
            return;
        }

        // Reset so reruns produce consistent demo data
        DB::table('linkages')->truncate();

        $rows = DB::table('provincial_director_kpis')
            ->where('kpi_id', $kpi->id)
            ->whereNotNull('accomplished')
            ->get(['provincial_director_id', 'year', 'accomplished']);

        $batch         = [];
        $totalRecords  = 0;
        $clampedRows   = 0;
        $now           = now();

        foreach ($rows as $row) {
            $raw   = $this->parseCount($row->accomplished);
            if ($raw <= 0) continue;

            $count = min($raw, self::MAX_PER_ROW);
            if ($raw > self::MAX_PER_ROW) $clampedRows++;

            $year  = (int) $row->year;

            for ($i = 1; $i <= $count; $i++) {
                $partner = self::PARTNERS[($i - 1) % count(self::PARTNERS)];
                $title   = self::TITLE_TEMPLATES[($i - 1) % count(self::TITLE_TEMPLATES)] . " (#{$i})";
                $type    = $i % 2 === 0 ? 'MOU' : 'MOA';

                // Spread the dates across the reporting year for realism
                $month = (($i - 1) % 12) + 1;
                $day   = (($i * 7) % 27) + 1;
                $dateSigned = sprintf('%04d-%02d-%02d', $year, $month, $day);

                $batch[] = [
                    'provincial_director_id' => $row->provincial_director_id,
                    'year'                   => $year,
                    'partner_organization'   => $partner,
                    'title'                  => $title,
                    'type'                   => $type,
                    'date_signed'            => $dateSigned,
                    'signatories'            => null,
                    'remarks'                => 'Auto-generated demo record',
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ];
                $totalRecords++;

                if (count($batch) >= self::FLUSH_EVERY) {
                    DB::table('linkages')->insert($batch);
                    $batch = [];
                }
            }

            // Realign the KPI accomplished value to the (possibly clamped) count
            // so the matrix display and the actual record count stay consistent.
            DB::table('provincial_director_kpis')
                ->where('provincial_director_id', $row->provincial_director_id)
                ->where('kpi_id', $kpi->id)
                ->where('year', $row->year)
                ->update(['accomplished' => (string) $count]);
        }

        if ($batch) {
            DB::table('linkages')->insert($batch);
        }

        $msg = "Linkages inserted: {$totalRecords} (from {$rows->count()} director-year rows)";
        if ($clampedRows > 0) {
            $msg .= "; {$clampedRows} rows clamped to " . self::MAX_PER_ROW . ' (source value looked like an amount, not a count)';
        }
        $this->command->info($msg . '.');
    }

    // Pulls the leading integer out of the accomplished value. Handles strings like
    // "5", " 5 ", "5 linkages", multi-line values, etc. Returns 0 if no number is found.
    private function parseCount(?string $raw): int
    {
        if ($raw === null) return 0;
        $raw = trim($raw);
        if ($raw === '') return 0;

        if (preg_match('/-?\d+/', $raw, $m)) {
            return max(0, (int) $m[0]);
        }
        return 0;
    }
}
