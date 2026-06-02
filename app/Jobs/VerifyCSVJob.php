<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class VerifyCSVJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly array  $rows,
        private readonly string $cacheKey,
    ) {}

    public function handle(): void
    {
        $results = [];

        // Pre-fetch existing emails and DOST IDs to avoid N+1 queries
        $existingEmails  = array_flip(User::select('email')->get()->pluck('email')->toArray());
        $existingDostIds = array_flip(User::select('dost_employee_id')->whereNotNull('dost_employee_id')->get()->pluck('dost_employee_id')->toArray());

        // Track duplicates within the CSV itself
        $seenEmails  = [];
        $seenDostIds = [];

        foreach ($this->rows as $i => $row) {
            $prefix          = trim($row['prefix']             ?? '');
            $firstName       = trim($row['first_name']         ?? '');
            $middleName      = trim($row['middle_name']        ?? '');
            $lastName        = trim($row['last_name']          ?? '');
            $suffix          = trim($row['suffix']             ?? '');
            $email           = trim($row['email']              ?? '');
            $dostId          = trim($row['dost_id_number']     ?? $row['dost_employee_id'] ?? '');
            $position        = trim($row['position']           ?? '');
            $lengthOfService = trim($row['length_of_service']  ?? '');

            $errors = [];

            if ($firstName === '') $errors['first_name'] = 'First name is required.';
            if ($lastName  === '') $errors['last_name']  = 'Last name is required.';

            if ($email === '') {
                $errors['email'] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format.';
            } elseif (isset($existingEmails[$email])) {
                $errors['email'] = 'Email already exists in the system.';
            } elseif (isset($seenEmails[$email])) {
                $errors['email'] = 'Duplicate email in this CSV.';
            } else {
                $seenEmails[$email] = true;
            }

            if ($dostId === '') {
                $errors['dost_id'] = 'DOST ID is required.';
            } elseif (isset($existingDostIds[$dostId])) {
                $errors['dost_id'] = 'DOST ID already exists in the system.';
            } elseif (isset($seenDostIds[$dostId])) {
                $errors['dost_id'] = 'Duplicate DOST ID in this CSV.';
            } else {
                $seenDostIds[$dostId] = true;
            }

            $results[] = [
                'row_index'   => $i,
                'status'      => empty($errors) ? 'valid' : 'invalid',
                'errors'      => $errors,
                'include'     => empty($errors),
                'data'        => [
                    'first_name'       => $firstName,
                    'middle_name'      => $middleName,
                    'last_name'        => $lastName,
                    'email'            => $email,
                    'dost_id'          => $dostId,
                    'position'         => $position,
                    'length_of_service'=> $lengthOfService,
                ],
            ];
        }

        Cache::put($this->cacheKey, [
            'status'  => 'done',
            'results' => $results,
        ], now()->addHour());
    }
}
