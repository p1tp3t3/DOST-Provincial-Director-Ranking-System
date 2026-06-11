<?php

namespace Database\Seeders;

use App\Helpers\CSVToDFHelper;
use App\Models\ActivityLog;
use App\Models\KPI;
use App\Models\KPICategory;
use App\Models\Profile;
use App\Models\Province;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        self::generate_provinces();
        self::generate_users();
        self::generate_kpi();
        $this->call(KPIScoreSeeder::class);
        $this->call(CSTCSeeder::class);
        ActivityLog::factory(100)->create();
    }

    // Classification per Excel "Province Directory" sheet (criterion: number of municipalities)
    private const CLASSIFICATION = [
        'micro' => [
            'Agusan del Norte', 'Apayao', 'Aurora', 'Batanes', 'Biliran',
            'Camiguin', 'Davao del Norte', 'Davao del Sur', 'Davao Occidental',
            'Davao Oriental', 'Dinagat Island', 'Guimaras', 'Kalinga',
            'Marinduque', 'Mountain Province', 'Quirino', 'Sarangani', 'Siquijor',
        ],
        'small' => [
            'Agusan del Sur', 'Aklan', 'Albay', 'Antique', 'Bataan', 'Benguet',
            'Bukidnon', 'Bulacan', 'Camarines Norte', 'Capiz', 'Catanduanes',
            'Cotabato (North)', 'Davao de Oro', 'Ifugao', 'La Union', 'Masbate',
            'Misamis Occidental', 'Negros Oriental', 'Nueva Vizcaya',
            'Occidental Mindoro', 'Oriental Mindoro', 'Pampanga', 'Rizal',
            'Romblon', 'Sorsogon', 'South Cotabato', 'Southern Leyte',
            'Sultan Kudarat', 'Sulu', 'Surigao del Norte', 'Surigao del Sur',
            'Tarlac', 'Zambales', 'Zamboanga Sibugay',
        ],
        'medium' => [
            'Abra', 'Batangas', 'Cagayan', 'Cavite', 'Eastern Samar',
            'Ilocos Norte', 'Laguna', 'Lanao del Norte', 'Misamis Oriental',
            'Northern Samar', 'Nueva Ecija', 'Palawan', 'Samar (Western Samar)',
            'Zamboanga del Norte', 'Zamboanga del Sur',
        ],
        'large' => [
            'Bohol', 'Camarines Sur', 'Cebu Province', 'Ilocos Sur', 'Iloilo',
            'Isabela', 'Leyte', 'Negros Occidental', 'Pangasinan', 'Quezon',
        ],
        'cstc' => [
            'CAMANAVA', 'PAMAMAZON', 'PAMAMARISAN', 'MUNTAPARLAS', 'ZCIC', 'Davao City',
        ],
    ];

    // CSTCs (CSTC = cluster of cities with no province of their own) and the
    // regions.csv `id` of the region their member cities actually belong to —
    // see public/geo/cstc-cities.geojson for the per-city breakdown.
    private const CSTC_PROVINCES = [
        ['name' => 'CAMANAVA',    'region_id' => 1,  'num_plantilla_employees' => 0, 'num_municipalities' => 0, 'num_cities' => 4], // NCR
        ['name' => 'PAMAMAZON',   'region_id' => 1,  'num_plantilla_employees' => 0, 'num_municipalities' => 0, 'num_cities' => 0], // NCR
        ['name' => 'PAMAMARISAN', 'region_id' => 1,  'num_plantilla_employees' => 4, 'num_municipalities' => 0, 'num_cities' => 0], // NCR
        ['name' => 'MUNTAPARLAS', 'region_id' => 1,  'num_plantilla_employees' => 4, 'num_municipalities' => 0, 'num_cities' => 3], // NCR
        ['name' => 'ZCIC',        'region_id' => 12, 'num_plantilla_employees' => 1, 'num_municipalities' => 0, 'num_cities' => 1], // Region IX
        ['name' => 'Davao City',  'region_id' => 14, 'num_plantilla_employees' => 3, 'num_municipalities' => 0, 'num_cities' => 1], // Region XI
    ];

    private static function get_category(string $name): string
    {
        foreach (self::CLASSIFICATION as $category => $list) {
            if (in_array($name, $list, true)) return $category;
        }
        return 'micro';
    }

    // Returns a map of regions.csv `id` → actual `region` table id
    private function generate_region(): array
    {
        $regions = CSVToDFHelper::get_df('regions.csv');
        $map     = [];

        foreach ($regions as $r) {
            $region = Region::create([
                'name'         => $r['name'],
                'island_under' => $r['island_under'],
            ]);
            $map[(int) $r['id']] = $region->id;
        }

        return $map;
    }

    private function generate_provinces()
    {
        $regionMap = self::generate_region();
        $provinces = CSVToDFHelper::get_df('provinces.csv');

        foreach ($provinces as $p) {
            Province::create([
                'name'                    => $p['name'],
                'category'                => self::get_category($p['name']),
                'region_id'               => $regionMap[(int) $p['region_id']],
                'num_plantilla_employees' => (int) ($p['num_plantilla_employees'] ?? 0),
                'num_municipalities'      => (int) ($p['num_municipalities']      ?? 0),
                'num_cities'              => (int) ($p['num_cities']              ?? 0),
            ]);
        }

        foreach (self::CSTC_PROVINCES as $c) {
            Province::create([
                'name'                    => $c['name'],
                'category'                => 'cstc',
                'region_id'               => $regionMap[$c['region_id']],
                'num_plantilla_employees' => $c['num_plantilla_employees'],
                'num_municipalities'      => $c['num_municipalities'],
                'num_cities'              => $c['num_cities'],
            ]);
        }
    }

    private function generate_users()
    {
        $provinces    = Province::all()->keyBy('name');
        $directorRows = CSVToDFHelper::get_df('provincial-directors.csv');
        $employeeRows = CSVToDFHelper::get_df('provincial-employees.csv');

        // Group employees by province name
        $employeesByProvince = [];
        foreach ($employeeRows as $emp) {
            $employeesByProvince[$emp['province']][] = $emp;
        }

        $superAdmin = User::factory()->create([
            'role'     => 'super_admin',
            'email'    => 'vinzmuloc@gmail.com',
            'username' => 'vinzmuloc',
        ]);
        Profile::create([
            'user_id'              => $superAdmin->id,
            'first_name'           => 'Vince',
            'middle_name'          => '',
            'last_name'            => '',
            'length_of_service'    => '',
            'education_attainment' => ['data' => []],
        ]);

        User::factory()->create(['role' => 'sub_admin']);

        // Create one Regional Admin per region
        foreach (Region::all() as $region) {
            User::factory()->create(['role' => 'regional_admin', 'region_id' => $region->id]);
        }

        foreach ($directorRows as $dir) {
            $province = $provinces[$dir['province']] ?? null;
            if (!$province) continue;

            // Create Provincial Director
            $director = User::factory()->create([
                'role'             => 'provincial_director',
                'dost_employee_id' => self::generate_emp_id(),
                'province_id'      => $province->id,
            ]);
            self::generate_profile_from_data($director, [
                'full_name'           => $dir['full_name'],
                'length_of_service'   => $dir['length_of_service_dost'],
                'education_bachelor'  => $dir['education_bachelor'],
                'education_master'    => $dir['education_master'],
                'education_doctorate' => $dir['education_doctorate'],
            ]);

            // Create Employees from real data
            $provEmployees = $employeesByProvince[$dir['province']] ?? [];
            foreach ($provEmployees as $i => $emp) {
                $employee = User::factory()->create([
                    'role'             => 'employee',
                    'province_id'      => $province->id,
                    'dost_employee_id' => "emp-p{$province->id}-" . sprintf('%02d', $i + 1),
                ]);
                self::generate_profile_from_data($employee, [
                    'full_name'           => $emp['full_name'],
                    'length_of_service'   => $emp['length_of_service'],
                    'education_bachelor'  => $emp['education_bachelor'],
                    'education_master'    => $emp['education_master'],
                    'education_doctorate' => $emp['education_doctorate'],
                    'position'            => $emp['position'],
                    'status'              => $emp['status'],
                    'work_specification'  => $emp['work_specification'],
                ]);
            }

            // Create Provincial Admin (no real data available)
            User::factory()->create(['role' => 'provincial_admin', 'province_id' => $province->id]);
            User::factory()->create(['role' => 'provincial_sub_admin', 'province_id' => $province->id]);
        }
    }

    private function generate_emp_id()
    {
        return sprintf(
            'dir-%s-%d',
            strtolower(fake()->lexify('???')),
            fake()->numberBetween(100, 999)
        );
    }

    private function generate_profile_from_data(User $user, array $data): void
    {
        [$firstName, $middleName, $lastName] = self::parse_name($data['full_name'] ?? '');

        $profile = Profile::create([
            'user_id'              => $user->id,
            'first_name'           => $firstName  ?: fake()->firstName(),
            'last_name'            => $lastName   ?: fake()->lastName(),
            'middle_name'          => $middleName ?: fake()->lastName(),
            'length_of_service'    => $data['length_of_service'] ?: (string) fake()->numberBetween(1, 3),
            'education_attainment' => [
                'data' => [
                    $data['education_bachelor']  ?? '',
                    $data['education_master']    ?? '',
                    $data['education_doctorate'] ?? '',
                ],
            ],
        ]);

        if ($user->role === 'employee') {
            $rawStatus = strtolower(trim($data['status'] ?? ''));
            DB::table('employee_profiles')->insert([
                'profile_id'         => $profile->id,
                'status'             => $rawStatus === 'cos' ? 'cos' : 'permanent',
                'position'           => $data['position'] ?: fake()->word(),
                'work_specification' => json_encode([
                    'data' => [$data['work_specification'] ?? '', '', ''],
                ]),
            ]);
        }
    }

    private static function parse_name(string $fullName): array
    {
        $fullName = trim($fullName);
        if ($fullName === '') return ['', '', ''];

        $parts = array_values(array_filter(explode(' ', $fullName)));
        $count = count($parts);

        if ($count === 1) {
            return [ucwords(strtolower($parts[0])), '', ''];
        }

        $first  = ucwords(strtolower($parts[0]));
        $last   = ucwords(strtolower($parts[$count - 1]));
        $middle = $count > 2
            ? ucwords(strtolower(implode(' ', array_slice($parts, 1, -1))))
            : '';

        return [$first, $middle, $last];
    }

    // Seeds the new PSTD Ranking Matrix structure: 3 categories (CORE/FUNCTIONAL/SUPPORT)
    // with 37 scored KPIs + 2 supporting input rows used to derive the % Delinquent SETUP KPI.
    private function generate_kpi()
    {
        foreach (CSVToDFHelper::get_df('kpi-categories.csv') as $row) {
            KPICategory::create([
                'code'       => $row['code'],
                'name'       => $row['name'],
                'weight'     => (float) $row['weight'],
                'sort_order' => (int) $row['sort_order'],
            ]);
        }

        $categoryIds = KPICategory::pluck('id', 'code')->toArray();

        foreach (CSVToDFHelper::get_df('kpis.csv') as $row) {
            KPI::create([
                'category_id'     => $categoryIds[$row['category_code']],
                'code'            => $row['code'],
                'name'            => $row['name'],
                'weight'          => (float) $row['weight'],
                'is_scored'       => (int) $row['is_scored'] === 1,
                'inverse_scoring' => (int) $row['inverse_scoring'] === 1,
                'derivation_type' => $row['derivation_type'] !== '' ? $row['derivation_type'] : null,
                'sort_order'      => (int) $row['sort_order'],
            ]);
        }
    }
}