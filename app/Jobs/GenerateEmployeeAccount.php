<?php

namespace App\Jobs;

use App\Mail\AccountMail;
use App\Models\EmployeeProfile;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GenerateEmployeeAccount implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly array $row,
        private readonly int   $provinceId,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) return;

        DB::transaction(function () {
            $username = $this->makeUsername($this->row);
            $email    = $this->row['email'] ?? ($username . '@dost.gov.ph');
            $dostId   = $this->row['dost_employee_id'] ?? ('emp-' . strtoupper(Str::random(8)));
            $password  = $this->makePassword();

            // Skip if email or DOST ID already exists
            if (User::where('email', $email)->orWhere('dost_employee_id', $dostId)->exists()) {
                return;
            }

            $user = User::create([
                'role'             => 'employee',
                'province_id'      => $this->provinceId,
                'dost_employee_id' => $dostId,
                'email'            => $email,
                'username'         => $username,
                'password'         => $password,   // default password = generated password
            ]);

            $profile = Profile::insertGetId([
                'user_id'              => $user->id,
                'prefix'               => $this->row['prefix']       ?? '',
                'first_name'           => $this->row['first_name']   ?? '',
                'middle_name'          => $this->row['middle_name']  ?? '',
                'last_name'            => $this->row['last_name']    ?? '',
                'suffix'               => $this->row['suffix']       ?? '',
                'length_of_service'    => $this->row['length_of_service'] ?? '0',
                'education_attainment' => json_encode($this->row['education_attainment'] ?? ['data' => []]),
            ]);

            EmployeeProfile::insert([
                'profile_id' => $profile,
                'position'   => $this->row['position'] ?? '',
                'status'     => $this->row['status']   ?? 'permanent',
                'work_specification' => json_encode($this->row['work_specification'] ?? ['data' => []]),
            ]);

            Mail::to($email)->queue(new AccountMail($user, $password));
        });
    }

    private function makeUsername(array $row): string
    {
        $base = strtolower(
            ($row['first_name'][0] ?? 'u') .
            ($row['last_name']    ?? 'ser')
        );
        $base = preg_replace('/[^a-z0-9]/', '', $base);

        $username = $base;
        if (User::where('username', $username)->exists()) {
            $username = $base . strtolower(Str::random(3));
        }
        return $username;
    }

    private function makePassword() {
        return Str::random(12);
    }
}
