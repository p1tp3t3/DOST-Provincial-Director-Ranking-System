<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

// Implements the official PSTD Ranking Matrix scoring + ranking logic.
//
//   1. Each scored KPI's accomplishment % vs target is mapped to an adjective score
//      (1.0 / 0.8 / 0.6 / 0.4 / 0.2 / 0.0) per config('ranking.score_bands_standard').
//   2. The "% Delinquent SETUP" KPI (inverse_scoring=true, derivation_type='delinquent_ratio')
//      derives its actual % from two input-only KPIs and uses the inverse bands.
//   3. weighted_score = kpi.weight * adjective_score
//   4. Total = sum of all KPI weighted scores (max 1.0)
//   5. Provinces are ranked WITHIN their classification tier (micro/small/medium/large),
//      then bucketed into Top / Average / Under by rank percentile.
class RankingService
{
    private array $kpis;             // [kpi_id => row]
    private array $kpiCategories;    // [category_id => row]
    private array $kpisByCode;       // [code => kpi_id]
    private array $bands;
    private array $bandsInverse;

    public function __construct()
    {
        $this->kpiCategories = DB::table('kpi_categories')->orderBy('sort_order')
            ->get()->keyBy('id')->map(fn($c) => (array) $c)->toArray();

        $this->kpis = DB::table('kpis')->orderBy('sort_order')
            ->get()->keyBy('id')->map(fn($k) => (array) $k)->toArray();

        $this->kpisByCode = [];
        foreach ($this->kpis as $id => $k) {
            $this->kpisByCode[$k['code']] = $id;
        }

        $this->bands        = config('ranking.score_bands_standard');
        $this->bandsInverse = config('ranking.score_bands_inverse');
    }

    public function availableYears(): array
    {
        return DB::table('provincial_director_kpis')
            ->distinct()->orderByDesc('year')->pluck('year')->values()->toArray();
    }

    // Returns the structure expected by the dashboard / map views:
    //   [
    //     <year> => [
    //       <province_category> => [   // 'micro' | 'small' | 'medium' | 'large'
    //         {
    //           rank, bucket, province, category, director, total, total_pct, adjective_label,
    //           subtotals: {CORE: float, FUNCTIONAL: float, SUPPORT: float},
    //           subtotals_pct: {CORE: float, FUNCTIONAL: float, SUPPORT: float},
    //           kpi_scores: [{kpi_id, score, weighted_score, actual_pct, adjective_label}]
    //         }, ...
    //       ]
    //     ]
    //   ]
    // $provinceIds optionally restricts the result to a subset of provinces
    // (e.g. all provinces within a given region for the Regional Admin views).
    public function rankAllYears(?array $provinceIds = null): array
    {
        $out = [];
        foreach ($this->availableYears() as $year) {
            $out[$year] = $this->rankByYear($year, $provinceIds);
        }
        return $out;
    }

    public function rankByYear(int $year, ?array $provinceIds = null): array
    {
        $records = $this->scoreAllProvinces($year, $provinceIds);

        // Group by classification tier so each tier is ranked independently
        $byCategory = [];
        foreach ($records as $r) {
            $byCategory[$r['category']][] = $r;
        }

        $out = [];
        foreach ($byCategory as $cat => $rows) {
            $out[$cat] = $this->rankAndBucket($rows);
        }
        return $out;
    }

    // Computes the weighted score for every province for the given year, including
    // provinces that have no data (they show up with total = 0). Pass $provinceIds
    // to restrict the result to a subset of provinces (e.g. one region).
    public function scoreAllProvinces(int $year, ?array $provinceIds = null): array
    {
        $directorNameSql = "CONCAT_WS(' ',
            NULLIF(TRIM(IFNULL(pr.prefix,'')), ''),
            NULLIF(TRIM(IFNULL(pr.first_name,'')), ''),
            NULLIF(TRIM(IFNULL(pr.middle_name,'')), ''),
            NULLIF(TRIM(IFNULL(pr.last_name,'')), ''),
            NULLIF(TRIM(IFNULL(pr.suffix,'')), '')
        )";

        $directors = DB::table('user_province as up')
            ->join('users as u', 'u.id', '=', 'up.user_id')
            ->where('u.role', 'provincial_director')
            ->select('up.province_id', 'up.user_id as director_id');

        $provinceMeta = DB::table('provinces as p')
            ->leftJoinSub($directors, 'd', 'd.province_id', '=', 'p.id')
            ->leftJoin('users as u', 'u.id', '=', 'd.director_id')
            ->leftJoin('profiles as pr', 'pr.user_id', '=', 'u.id')
            ->leftJoin('region as r', 'r.id', '=', 'p.region_id')
            ->when($provinceIds !== null, fn($q) => $q->whereIn('p.id', $provinceIds))
            ->select(
                'p.id as province_id',
                'p.name as province',
                'p.category',
                'p.region_id as region_id',
                'r.name as region_name',
                'r.island_under as island_under',
                'u.id as director_id',
                DB::raw("$directorNameSql as director")
            )->get()->keyBy('province_id');

        $rows = DB::table('provincial_director_kpis as pk')
            ->join('users as u', 'u.id', '=', 'pk.provincial_director_id')
            ->join('user_province as up', 'up.user_id', '=', 'u.id')
            ->where('pk.year', $year)
            ->when($provinceIds !== null, fn($q) => $q->whereIn('up.province_id', $provinceIds))
            ->select('up.province_id', 'pk.kpi_id', 'pk.target', 'pk.accomplished')
            ->get();

        // valuesByProvince[province_id][kpi_id] = ['target' => ..., 'accomplished' => ...]
        $valuesByProvince = [];
        foreach ($rows as $r) {
            $valuesByProvince[$r->province_id][$r->kpi_id] = [
                'target'       => $r->target,
                'accomplished' => $r->accomplished,
            ];
        }

        $records = [];
        foreach ($provinceMeta as $provinceId => $meta) {
            $records[] = $this->scoreProvince(
                $provinceId, (array) $meta,
                $valuesByProvince[$provinceId] ?? []
            );
        }
        return $records;
    }

    private function scoreProvince(int $provinceId, array $meta, array $values): array
    {
        $kpiScores  = [];
        $subtotals  = []; // category_id => float
        foreach ($this->kpiCategories as $cat) {
            $subtotals[$cat['code']] = 0.0;
        }

        foreach ($this->kpis as $kpiId => $kpi) {
            if (!$kpi['is_scored']) continue;

            $catCode = $this->kpiCategories[$kpi['category_id']]['code'];
            $score   = $this->scoreKpi($kpiId, $kpi, $values);
            $weighted = $score['score'] * (float) $kpi['weight'];
            $subtotals[$catCode] += $weighted;

            $kpiScores[] = [
                'kpi_id'          => $kpiId,
                'code'            => $kpi['code'],
                'category_code'   => $catCode,
                'actual_pct'      => $score['actual_pct'],
                'score'           => $score['score'],
                'adjective_label' => $score['adjective_label'],
                'weighted_score'  => round($weighted, 6),
            ];
        }

        $total = array_sum($subtotals);

        // Status determines whether this province competes for a rank:
        //   no_director — vacant PSTD position; cannot have submitted anything
        //   no_data     — director assigned but hasn't submitted KPI data this year
        //   ranked      — has director + at least one KPI value for this year
        $hasDirector = !empty($meta['director_id']);
        $hasData     = !empty($values);
        $status      = !$hasDirector ? 'no_director' : (!$hasData ? 'no_data' : 'ranked');

        $totalBand = $this->lookupStandardBand($total * 100);

        return [
            'province_id'      => $provinceId,
            'province'         => $meta['province'],
            'region'           => $meta['region'] ?? null,
            'category'         => $meta['category'],
            'region_id'        => $meta['region_id'],
            'region_name'      => $meta['region_name'],
            'island_under'     => $meta['island_under'],
            'director'         => $meta['director'] ?? null,
            'director_id'      => $meta['director_id'] ?? null,
            'status'           => $status,
            'total'            => round($total, 6),
            'total_pct'        => round($total * 100, 2),
            'adjective_label'  => $totalBand['label'],
            'subtotals'        => array_map(fn($v) => round($v, 6), $subtotals),
            'subtotals_pct'    => array_map(fn($v) => round($v * 100, 2), $subtotals),
            'kpi_scores'       => $kpiScores,
        ];
    }

    // Returns ['score'=>float, 'actual_pct'=>?float, 'adjective_label'=>string]
    private function scoreKpi(int $kpiId, array $kpi, array $values): array
    {
        // Derived KPI: % Delinquent SETUP = delinquent_count / ongoing_count * 100, inverse-scored
        if ($kpi['derivation_type'] === 'delinquent_ratio') {
            $ongoingId    = $this->kpisByCode['input_setup_ongoing_count']    ?? null;
            $delinquentId = $this->kpisByCode['input_setup_delinquent_count'] ?? null;

            $ongoing    = self::parseNumber($values[$ongoingId]['accomplished']    ?? null);
            $delinquent = self::parseNumber($values[$delinquentId]['accomplished'] ?? null);

            if ($ongoing === null || $ongoing <= 0 || $delinquent === null) {
                return ['score' => 0.0, 'actual_pct' => null, 'adjective_label' => 'No data'];
            }
            $pct  = $delinquent / $ongoing * 100;
            $band = $this->lookupInverseBand($pct);
            return ['score' => $band['score'], 'actual_pct' => round($pct, 2), 'adjective_label' => $band['label']];
        }

        // Standard: % accomplishment = accomplished / target * 100
        $target = self::parseNumber($values[$kpiId]['target']       ?? null);
        $acc    = self::parseNumber($values[$kpiId]['accomplished'] ?? null);

        if ($target === null || $target <= 0 || $acc === null) {
            return ['score' => 0.0, 'actual_pct' => null, 'adjective_label' => 'No data'];
        }
        $pct  = $acc / $target * 100;
        $band = $kpi['inverse_scoring']
            ? $this->lookupInverseBand($pct)
            : $this->lookupStandardBand($pct);
        return ['score' => $band['score'], 'actual_pct' => round($pct, 2), 'adjective_label' => $band['label']];
    }

    private function lookupStandardBand(float $pct): array
    {
        foreach ($this->bands as $band) {
            if ($pct >= $band['min']) return $band;
        }
        return end($this->bands);
    }

    private function lookupInverseBand(float $pct): array
    {
        foreach ($this->bandsInverse as $band) {
            if ($pct <= $band['max']) return $band;
        }
        return end($this->bandsInverse);
    }

    // Ranks within a single classification tier and assigns Top / Average / Under buckets.
    //   - Provinces with status != 'ranked' (no director or no submitted data) are
    //     EXCLUDED from the bucket math — they shouldn't dilute N or steal an
    //     "Under" slot from a province that genuinely underperformed. They're
    //     appended at the end of the returned array with rank=null, bucket=null.
    //   - Tiebreaker: total DESC, then CORE subtotal DESC, FUNCTIONAL DESC, SUPPORT DESC,
    //     then province name ASC (deterministic).
    //   - Bucketing rule: Top = round(N * top_pct, min 1), Under = round(N * under_pct, min 1),
    //     Average absorbs the remainder. Skipped entirely when N < min_group_size_for_buckets.
    private function rankAndBucket(array $rows): array
    {
        $ranked   = array_values(array_filter($rows, fn($r) => ($r['status'] ?? 'ranked') === 'ranked'));
        $unranked = array_values(array_filter($rows, fn($r) => ($r['status'] ?? 'ranked') !== 'ranked'));

        usort($ranked, function ($a, $b) {
            $cmp = $b['total'] <=> $a['total'];
            if ($cmp !== 0) return $cmp;
            foreach (['CORE', 'FUNCTIONAL', 'SUPPORT'] as $c) {
                $cmp = ($b['subtotals'][$c] ?? 0) <=> ($a['subtotals'][$c] ?? 0);
                if ($cmp !== 0) return $cmp;
            }
            return strcmp($a['province'], $b['province']);
        });

        $n      = count($ranked);
        $minN   = (int) config('ranking.min_group_size_for_buckets', 5);
        $topPct = (float) config('ranking.buckets.top_pct',   0.20);
        $undPct = (float) config('ranking.buckets.under_pct', 0.20);

        $useBuckets = $n >= $minN;
        $topCount   = 0;
        $underCount = 0;
        if ($useBuckets) {
            $topCount   = max(1, (int) round($n * $topPct));
            $underCount = max(1, (int) round($n * $undPct));
            if ($topCount + $underCount >= $n) {
                $underCount = max(1, $n - $topCount - 1);
            }
        }
        $avgEnd = $n - $underCount;

        foreach ($ranked as $i => &$row) {
            $row['rank'] = $i + 1;
            if (!$useBuckets) {
                $row['bucket'] = null;
            } elseif ($i < $topCount) {
                $row['bucket'] = 'Top';
            } elseif ($i < $avgEnd) {
                $row['bucket'] = 'Average';
            } else {
                $row['bucket'] = 'Low';
            }
        }
        unset($row);

        // Unranked provinces sort alphabetically and carry no rank or bucket
        usort($unranked, fn($a, $b) => strcmp($a['province'], $b['province']));
        foreach ($unranked as &$row) {
            $row['rank']   = null;
            $row['bucket'] = null;
        }
        unset($row);

        return array_merge($ranked, $unranked);
    }

    // Parses a raw string from the legacy data into a number, or null if unparseable.
    // Handles: clean numbers, percentages stored as decimals, currency strings with
    // commas, and compound "X - keyword" / "keyword: X" multi-line values by summing
    // the numeric components.
    public static function parseNumber(mixed $raw): ?float
    {
        if ($raw === null) return null;
        $s = trim((string) $raw);
        if ($s === '' || $s === '-' || strcasecmp($s, 'n/a') === 0) return null;

        $clean = str_replace([','], '', $s);
        if (preg_match('/^-?\d+(\.\d+)?$/', $clean)) return (float) $clean;

        // Compound value: sum every numeric token across all lines.
        if (preg_match_all('/-?\d+(?:\.\d+)?/', $clean, $m)) {
            if (count($m[0]) === 0) return null;
            $sum = 0.0;
            foreach ($m[0] as $n) $sum += (float) $n;
            return $sum;
        }
        return null;
    }
}
