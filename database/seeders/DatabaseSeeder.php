<?php

namespace Database\Seeders;

use App\Helpers\CSVToDFHelper;
use App\Helpers\PexelProfilePictureGeneratorHelper;
use App\Models\KPI;
use App\Models\Profile;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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

    private function generate_provinces() { 
        $provices = CSVToDFHelper::get_df('provinces.csv');
        foreach($provices as $p)
            Province::create([
                'name' => $p['name'],
                'category' => $p['category'],
                'num_plantilla_employees' => fake()->randomNumber(1, 20),
                'num_municipalities' => fake()->randomNumber(1),
                'num_cities' => fake()->randomNumber(1, 20),
            ]);
    }

    private function generate_users() 
    {
        $provinces = Province::all();

        File::deleteDirectory(storage_path('app/public/profile-pictures'));
        User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['role' => 'sub_admin']);
        foreach ($provinces as $p) {

            // 1. Create exactly 1 Provincial Director per province
            $director = User::factory()->create([
                                        'role' => 'provincial_director', 
                                        'dost_employee_id' => self::generate_emp_id(), 
                                        'province_id' => $p->id
                                    ]);
            self::generate_profile($director);

            // 2. Create 10 Employees per province with GUARANTEED unique IDs
            for ($i = 1; $i <= 10; $i++) {
                $employee = User::factory()->create([
                    'role' => 'employee', 
                    'province_id' => $p->id, 
                    // Generates format like: emp-p12-05 (Province ID + Iterator index)
                    'dost_employee_id' => "emp-p{$p->id}-" . sprintf('%02d', $i)
                ]);
                self::generate_profile($employee);
            }

            // 3. Create Admins per province
            User::factory()->create(['role' => 'provincial_admin', 'province_id' => $p->id]);
            User::factory()->create(['role' => 'provincial_sub_admin', 'province_id' => $p->id]);
        }
    }

    private function generate_emp_id() {
        // Generates a wide-span format like: dir-xyz-742
        return sprintf(
            'dir-%s-%d', 
            strtolower(fake()->lexify('???')), 
            fake()->numberBetween(100, 999)
        );
    }


    private function generate_profile($d) {
        //$profile_picture = PexelProfilePictureGeneratorHelper::generate('random people profile picture');
        $profile = Profile::create([
            'user_id' => $d->id,
            'first_name' => fake()->firstName(),
            //'profile_picture' => $profile_picture,
            'last_name' => fake()->lastName(),
            'middle_name' => fake()->lastName(),
            'length_of_service' => fake()->numberBetween(1, 3),
            'education_attainment' => [
                'data' => [
                    fake()->sentences(3, true),
                    fake()->sentences(3, true),
                    fake()->sentences(3, true),
                ]
            ],
        ]);
        if($d->role == 'employee') {
            DB::table('employee_profiles')->insert([
                'profile_id' => $profile->id,
                'status' => fake()->randomElement(['permanent', 'cos']),
                'position' => fake()->word(),
                'work_specification' => json_encode([
                    'data' => [
                        fake()->sentences(3, true),
                        fake()->sentences(3, true),
                        fake()->sentences(3, true),
                    ]
                ]),
            ]);
        }
    }

    private function generate_kpi() {
        $kpi = CSVToDFHelper::get_df('kpi.csv');
        $kpi_outcome = CSVToDFHelper::get_df('kpi-outcome.csv');

        foreach($kpi as $k) {
            $kpi = KPI::create([
                        'id' => $k['id'],
                        'outcome_title' => $k['outcome'],
                    ]);
            foreach($kpi_outcome as $outcome) {
                DB::table('kpi_subrows')->insert([
                    'kpi_id' => $kpi->id,
                    'description' => $outcome['description']
                ]);
            }
        }
    }
}
