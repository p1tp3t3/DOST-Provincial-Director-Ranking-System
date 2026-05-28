<?php

namespace Database\Seeders;

use App\Helpers\CSVToDFHelper;
use App\Models\KPI;
use App\Models\Profile;
use App\Models\Province;
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
    }

    private function generate_provinces()
    {
        $provinces = CSVToDFHelper::get_df('provinces.csv');
        foreach ($provinces as $p) {
            Province::create([
                'name'                   => $p['name'],
                'num_plantilla_employees' => (int) ($p['num_plantilla_employees'] ?? 0),
                'num_municipalities'      => (int) ($p['num_municipalities']      ?? 0),
                'num_cities'              => (int) ($p['num_cities']              ?? 0),
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

    private function generate_kpi()
    {
        $kpi         = CSVToDFHelper::get_df('kpi.csv');
        $kpi_outcome = CSVToDFHelper::get_df('kpi-outcome.csv');

        foreach ($kpi as $k) {
            $subRow = [];
            $j = 1;
            foreach ($kpi_outcome as $outcome) {
                $subRow[] = [
                    'id'          => $j,
                    'description' => $outcome['description'],
                ];
                $j++;
            }
            KPI::create([
                'id'            => $k['id'],
                'outcome_title' => $k['outcome'],
                'sub_rows'      => json_encode($subRow),
            ]);
        }
    }
}
