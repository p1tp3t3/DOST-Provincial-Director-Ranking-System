<?php

namespace Database\Seeders;

use App\Helpers\CSVToDFHelper;
use App\Models\KPI;
use App\Models\Profile;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CSTCSeeder extends Seeder
{
    private const CLUSTERS = ['CAMANAVA', 'PAMAMAZON', 'PAMAMARISAN', 'MUNTAPARLAS', 'ZCIC', 'Davao City'];

    public function run(): void
    {
        $directorRows = CSVToDFHelper::get_df('cstc-directors.csv');
        $kpiRows      = CSVToDFHelper::get_df('cstc-kpi-scores.csv');

        // The CSTC clusters now live in the Large tier, so look them up by name.
        $provinces = Province::whereIn('name', self::CLUSTERS)->get()->keyBy('name');
        $directorMap    = [];  // province name → director id (newly created only, for KPI insert)
        $allDirectorMap = [];  // province name → director id (all, for reference)

        foreach ($directorRows as $row) {
            $province = $provinces[$row['province']] ?? null;
            if (!$province) {
                $this->command->warn("No province found for CSTC: {$row['province']}");
                continue;
            }

            // Skip creating if director already exists
            $existing = User::where('province_id', $province->id)
                ->where('role', 'provincial_director')
                ->first();
            if ($existing) {
                $allDirectorMap[$row['province']] = $existing->id;
                continue;
            }

            [$first, $middle, $last] = self::parseName($row['full_name']);

            $user = User::factory()->create([
                'role'             => 'provincial_director',
                'province_id'      => $province->id,
                'dost_employee_id' => sprintf('cstc-%s-%d', strtolower(substr($row['province'], 0, 3)), rand(100, 999)),
            ]);

            Profile::create([
                'user_id'              => $user->id,
                'first_name'           => $first  ?: 'CSTD',
                'middle_name'          => $middle ?: '',
                'last_name'            => $last   ?: $row['province'],
                'length_of_service'    => $row['length_of_service'] ?: '1',
                'education_attainment' => [
                    'data' => [
                        $row['education_bachelor']  ?? '',
                        $row['education_master']    ?? '',
                        $row['education_doctorate'] ?? '',
                    ],
                ],
            ]);

            $directorMap[$row['province']]    = $user->id;
            $allDirectorMap[$row['province']] = $user->id;
            $this->command->line("  Created director for <info>{$row['province']}</info>: {$first} {$last}");
        }

        // Load KPI scores only for newly created directors. Same legacy 54-subrow → new
        // 37-KPI mapping as KPIScoreSeeder; unmapped subrow IDs are silently skipped.
        $kpiIdByCode = KPI::pluck('id', 'code')->toArray();
        $batch       = [];
        $missed      = [];
        foreach ($kpiRows as $row) {
            $directorId = $directorMap[$row['province']] ?? null;
            if (!$directorId) {
                if (!isset($allDirectorMap[$row['province']])) $missed[$row['province']] = true;
                continue;
            }
            $code  = KPIScoreSeeder::SUBROW_TO_KPI_CODE[(int) $row['subrow_id']] ?? null;
            $kpiId = $code !== null ? ($kpiIdByCode[$code] ?? null) : null;
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

        if ($missed) {
            $this->command->warn('No director map for: ' . implode(', ', array_keys($missed)));
        }

        $this->command->info("CSTC KPI scores inserted: " . count($batch));

        // Create employees (skip if already seeded for that province)
        $employeeRows   = CSVToDFHelper::get_df('cstc-employees.csv');
        $seededProvinces = User::where('role', 'employee')
            ->whereIn('province_id', $provinces->pluck('id'))
            ->pluck('province_id')
            ->unique()
            ->toArray();
        $empCount = 0;

        foreach ($employeeRows as $i => $row) {
            $province = $provinces[$row['province']] ?? null;
            if (!$province) continue;
            if (in_array($province->id, $seededProvinces)) continue;

            [$first, $middle, $last] = self::parseName($row['full_name']);

            $user = User::factory()->create([
                'role'             => 'employee',
                'province_id'      => $province->id,
                'dost_employee_id' => sprintf('cstc-emp-%s-%02d', strtolower(substr($row['province'], 0, 3)), $i + 1),
            ]);

            $rawStatus = strtolower(trim($row['status'] ?? ''));
            $profile = Profile::create([
                'user_id'              => $user->id,
                'first_name'           => $first  ?: fake()->firstName(),
                'middle_name'          => $middle ?: '',
                'last_name'            => $last   ?: fake()->lastName(),
                'length_of_service'    => $row['length_of_service'] ?: '1',
                'education_attainment' => [
                    'data' => [
                        $row['education_bachelor']  ?? '',
                        $row['education_master']    ?? '',
                        $row['education_doctorate'] ?? '',
                    ],
                ],
            ]);

            DB::table('employee_profiles')->insert([
                'profile_id'         => $profile->id,
                'status'             => $rawStatus === 'cos' ? 'cos' : 'permanent',
                'position'           => $row['position'] ?: 'Staff',
                'work_specification' => json_encode([
                    'data' => [$row['work_specialization'] ?? '', '', ''],
                ]),
            ]);

            $empCount++;
        }

        $this->command->info("CSTC employees inserted: {$empCount}");
    }

    private static function parseName(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') return ['', '', ''];

        // Handle "LASTNAME, FIRSTNAME MIDDLE" format
        if (str_contains($fullName, ', ')) {
            [$last, $rest] = explode(', ', $fullName, 2);
            $parts = array_values(array_filter(explode(' ', $rest)));
            $first  = ucwords(strtolower($parts[0] ?? ''));
            $middle = ucwords(strtolower(implode(' ', array_slice($parts, 1))));
            $last   = ucwords(strtolower($last));
            return [$first, $middle, $last];
        }

        // Handle "FIRSTNAME [MIDDLE] LASTNAME" format
        $parts = array_values(array_filter(explode(' ', $fullName)));
        $count = count($parts);
        if ($count === 1) return [ucwords(strtolower($parts[0])), '', ''];
        $first  = ucwords(strtolower($parts[0]));
        $last   = ucwords(strtolower($parts[$count - 1]));
        $middle = $count > 2 ? ucwords(strtolower(implode(' ', array_slice($parts, 1, -1)))) : '';
        return [$first, $middle, $last];
    }
}
