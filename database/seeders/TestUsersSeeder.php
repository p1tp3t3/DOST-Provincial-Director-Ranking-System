<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $provinceId = DB::table('provinces')->value('id');

        $users = [
            [
                'role'     => 'super_admin',
                'email'    => 'superadmin@pdris.test',
                'username' => 'superadmin',
                'name'     => ['Test', '', 'SuperAdmin'],
            ],
            [
                'role'     => 'sub_admin',
                'email'    => 'subadmin@pdris.test',
                'username' => 'subadmin',
                'name'     => ['Test', '', 'SubAdmin'],
            ],
            [
                'role'        => 'provincial_admin',
                'email'       => 'provadmin@pdris.test',
                'username'    => 'provadmin',
                'province_id' => $provinceId,
                'name'        => ['Test', '', 'ProvAdmin'],
            ],
            [
                'role'        => 'provincial_director',
                'email'       => 'director@pdris.test',
                'username'    => 'director',
                'province_id' => $provinceId,
                'name'        => ['Test', '', 'Director'],
            ],
            [
                'role'        => 'employee',
                'email'       => 'employee@pdris.test',
                'username'    => 'employee',
                'province_id' => $provinceId,
                'name'        => ['Test', '', 'Employee'],
            ],
        ];

        foreach ($users as $data) {
            [$user, $created] = [
                User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'username'    => $data['username'],
                        'password'    => Hash::make('password'),
                        'role'        => $data['role'],
                        'province_id' => $data['province_id'] ?? null,
                    ]
                ),
                null,
            ];

            Profile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name'           => $data['name'][0],
                    'middle_name'          => $data['name'][1],
                    'last_name'            => $data['name'][2],
                    'length_of_service'    => '1',
                    'education_attainment' => ['data' => []],
                ]
            );

            $this->command->line("  Created <info>{$data['role']}</info> → {$data['email']} / password");
        }
    }
}
