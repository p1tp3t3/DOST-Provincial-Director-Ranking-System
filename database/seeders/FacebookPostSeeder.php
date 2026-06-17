<?php

namespace Database\Seeders;

use App\Models\KPI;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// Backfills the facebook_posts table with dummy records that match each
// director's previously-typed "accomplished" value for `supp_facebook_posts`.
// Same shape as LinkageSeeder.
class FacebookPostSeeder extends Seeder
{
    private const MAX_PER_ROW = 60;
    private const FLUSH_EVERY = 500;

    private const TITLES = [
        'SETUP Beneficiary Success Story',
        'New Innovation Hub Inauguration',
        'Provincial S&T Week Highlights',
        'GIA Project Launch Announcement',
        'Community Empowerment Initiative',
        'DRRM Training Recap',
        'Featured MSME of the Month',
        'CEST Program Updates',
        'Director Visits Local Cooperative',
        'Tech Transfer Ceremony Recap',
        'Scholarship Program Announcement',
        'Customer Spotlight: Local Farmer',
        'Industry Partnership Highlight',
        'S&T Promotional Campaign',
        'Press Release Highlights',
    ];

    private const TYPES = ['Text', 'Photo', 'Video', 'Link', 'Event'];

    public function run(): void
    {
        $kpi = KPI::where('code', 'supp_facebook_posts')->first();
        if (!$kpi) {
            $this->command->warn('KPI supp_facebook_posts not found — skipping.');
            return;
        }

        DB::table('facebook_posts')->truncate();

        $rows = DB::table('provincial_director_kpis')
            ->where('kpi_id', $kpi->id)
            ->whereNotNull('accomplished')
            ->get(['provincial_director_id', 'year', 'accomplished']);

        $batch        = [];
        $totalRecords = 0;
        $clampedRows  = 0;
        $now          = now();

        foreach ($rows as $row) {
            $raw = $this->parseCount($row->accomplished);
            if ($raw <= 0) continue;

            $count = min($raw, self::MAX_PER_ROW);
            if ($raw > self::MAX_PER_ROW) $clampedRows++;

            $year = (int) $row->year;

            for ($i = 1; $i <= $count; $i++) {
                $title = self::TITLES[($i - 1) % count(self::TITLES)] . " (#{$i})";
                $type  = self::TYPES[($i - 1) % count(self::TYPES)];

                // Spread the dates across the reporting year for realism
                $month = (($i - 1) % 12) + 1;
                $day   = (($i * 3) % 27) + 1;
                $dateSigned = sprintf('%04d-%02d-%02d', $year, $month, $day);

                $batch[] = [
                    'provincial_director_id' => $row->provincial_director_id,
                    'year'                   => $year,
                    'title'                  => $title,
                    'post_url'               => "https://www.facebook.com/dost.demo/posts/{$row->provincial_director_id}-{$year}-{$i}",
                    'post_type'              => $type,
                    'date_posted'            => $dateSigned,
                    'caption'                => 'Auto-generated demo post.',
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ];
                $totalRecords++;

                if (count($batch) >= self::FLUSH_EVERY) {
                    DB::table('facebook_posts')->insert($batch);
                    $batch = [];
                }
            }

            // Realign the KPI accomplished value to the (possibly clamped) count
            DB::table('provincial_director_kpis')
                ->where('provincial_director_id', $row->provincial_director_id)
                ->where('kpi_id', $kpi->id)
                ->where('year', $row->year)
                ->update(['accomplished' => (string) $count]);
        }

        if ($batch) {
            DB::table('facebook_posts')->insert($batch);
        }

        $msg = "Facebook posts inserted: {$totalRecords} (from {$rows->count()} director-year rows)";
        if ($clampedRows > 0) {
            $msg .= "; {$clampedRows} rows clamped to " . self::MAX_PER_ROW . ' (source value looked like an amount, not a count)';
        }
        $this->command->info($msg . '.');
    }

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
