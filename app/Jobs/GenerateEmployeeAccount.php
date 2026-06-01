<?php

namespace App\Jobs;

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
                'password'         => bcrypt($dostId),   // default password = DOST ID
            ]);

            $profile = Profile::insertGetId([
                'user_id'              => $user->id,
                'first_name'           => $this->row['first_name']  ?? '',
                'middle_name'          => $this->row['middle_name'] ?? '',
                'last_name'            => $this->row['last_name']   ?? '',
                'length_of_service'    => $this->row['length_of_service'] ?? '0',
                'education_attainment' => json_encode($this->row['education_attainment'] ?? ['data' => []]),
            ]);

            EmployeeProfile::insert([
                'profile_id' => $profile,
                'position'   => $this->row['position'] ?? '',
                'status'     => $this->row['status']   ?? 'permanent',
                'work_specification' => json_encode($this->row['work_specification'] ?? ['data' => []]),
            ]);

            Mail::raw(
                "An account has been created for you on the Director Ranking Information System.\n\n" .
                "ID: {$dostId}\n" .
                "Username: {$user->username}\n" .
                "Password: {$dostId}\n\n" .
                "Please log in and change your password as soon as possible.",
                function ($message) use ($email) {
                    $message->to($email)
                            ->subject('Your New Account on Director Ranking Information System');
                }
            );
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
        $suffix   = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base . $suffix++;
        }
        return $username;
    }
}
