<?php

namespace App\Http\Controllers\Modules\User;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\AdminRegistrationRequest;
use App\Http\Requests\User\UserRegistrationRequest;
use App\Http\Resources\UserResource;
use App\Jobs\GenerateEmployeeAccount;
use App\Models\ActivityLog;
use App\Models\Province;
use App\Models\Region;
use App\Jobs\VerifyCSVJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\EmployeeProfile;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return inertia('Admin/Users/Main', [
            'users'     => self::get_users($request->input('search'), $request->input('role')),
            'search'    => $request->input('search', ''),
            'role'      => $request->input('role', ''),
            'provinces' => Province::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $user = User::with('profile')->findOrFail($id);

        $validated = $request->validate([
            'role'                  => ['required', 'in:super_admin,sub_admin,provincial_admin,provincial_director,employee'],
            'province_id'           => ['nullable', 'exists:provinces,id'],
            'dost_employee_id'      => ['nullable', 'string', 'unique:users,dost_employee_id,' . $id],
            'username'              => ['required', 'string', 'unique:users,username,' . $id],
            'email'                 => ['required', 'email', 'unique:users,email,' . $id],
            'password'              => ['nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['nullable', 'string'],
            'prefix'                => ['nullable', 'string', 'max:20'],
            'first_name'            => ['required', 'string', 'max:100'],
            'middle_name'           => ['nullable', 'string', 'max:100'],
            'last_name'             => ['required', 'string', 'max:100'],
            'suffix'                => ['nullable', 'string', 'max:20'],
            'length_of_service'     => ['nullable', 'string', 'max:50'],
        ]);

        DB::beginTransaction();
        try {
            $userFields = [
                'role'             => $validated['role'],
                'dost_employee_id' => $validated['dost_employee_id'],
                'username'         => $validated['username'],
                'email'            => $validated['email'],
            ];

            if (!empty($validated['password'])) {
                $userFields['password'] = $validated['password'];
            }

            $user->update($userFields);

            $user->provinces()->sync($validated['province_id'] ? [$validated['province_id']] : []);

            $user->profile->update([
                'prefix'            => $validated['prefix'],
                'first_name'        => $validated['first_name'],
                'middle_name'       => $validated['middle_name'],
                'last_name'         => $validated['last_name'],
                'suffix'            => $validated['suffix'],
                'length_of_service' => $validated['length_of_service'],
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json(['message' => 'User updated successfully.']);
    }

    public function toggle_activation(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'You cannot change the activation status of your own account.'], 403);
        }

        $user->update(['activate' => !$user->activate]);

        return back();
    }

    public function destroy(int $id)
    {
        $actor = Auth::user();
        $user  = User::with('profile')->findOrFail($id);

        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'You cannot delete your own account.'], 403);
        }

        // Provincial admins are scoped to their own province, same as the user
        // list and manual registration — they can't reach across provinces.
        if ($actor->role === 'provincial_admin' && $user->province_id !== $actor->province_id) {
            return response()->json(['message' => 'You can only delete users in your own province.'], 403);
        }

        DB::beginTransaction();
        try {
            $profile = $user->profile;
            if ($profile) {
                EmployeeProfile::where('profile_id', $profile->id)->delete();
                $profile->delete();
            }

            // Every table below has a non-nullable RESTRICT foreign key to this
            // user, so all references must be cleared before the account can be
            // removed. Deleting a director simply vacates their province — the
            // provinces table is never touched; the user just stops occupying one.
            ActivityLog::where('user_id', $user->id)->delete();
            DB::table('provincial_director_kpis')->where('provincial_director_id', $user->id)->delete();
            DB::table('linkages')->where('provincial_director_id', $user->id)->delete();
            DB::table('facebook_posts')->where('provincial_director_id', $user->id)->delete();
            DB::table('notifications')->where('sender_id', $user->id)->orWhere('receiver_id', $user->id)->delete();
            DB::table('provincial_members')->where('provincial_admin_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $user->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        // $user still holds the in-memory snapshot needed for the audit line.
        ActivityLogHelper::deleteUser($user);

        return back();
    }

    public function admin_index(Request $request)
    {
        $search = $request->input('search');
        $role   = $request->input('role');

        $admins = User::with(['profile', 'provinces', 'region'])
                      ->whereIn('role', ['super_admin', 'sub_admin', 'regional_admin', 'provincial_admin'])
                      ->when($search, function ($q, $search) {
                          $q->where(function ($q) use ($search) {
                              $q->whereHas('profile', fn($p) =>
                                  $p->whereRaw("CONCAT(first_name, ' ', COALESCE(middle_name,''), ' ', last_name) LIKE ?", ["%{$search}%"])
                              )
                              ->orWhere('email', 'like', "%{$search}%")
                              ->orWhere('username', 'like', "%{$search}%");
                          });
                      })
                      ->when($role, fn($q, $role) => $q->where('role', $role))
                      ->latest('created_at')
                      ->paginate(20)
                      ->withQueryString();

        return inertia('Admin/Users/Admins', [
            'admins' => UserResource::collection($admins),
            'search' => $search ?? '',
            'role'   => $role ?? '',
        ]);
    }

    public function manual_registration_index()
    {
        $role = self::get_role();
        return inertia("$role/Users/Register/Manual", [
            'provinces' => Province::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function auto_registration_index()
    {
        $role = self::get_role();
        return inertia("$role/Users/Register/AutoGenerator");
    }

    // ── Manual registration ────────────────────────────────────────

    public function store(UserRegistrationRequest $request)
    {
        $data = $request->validated();
        $actor = Auth::user();

        DB::beginTransaction();
        try {
            // Provincial admins can only register into their own province — never
            // trust a client-submitted value for them, even if one were sent.
            $provinceId = $actor->role === 'provincial_admin' ? $actor->province_id : ($data['province'] ?? null);
            $province   = Province::findOrFail($provinceId);

            $user = User::create([
                'role'             => $data['role'],
                'dost_employee_id' => $data['dost_employee_id'],
                'email'            => $data['email'],
                'username'         => $data['username'],
                'password'         => $data['password'],
            ]);

            $user->provinces()->attach($province->id);

            $profileId = Profile::insertGetId([
                'user_id'              => $user->id,
                'first_name'           => $data['first_name'],
                'middle_name'          => $data['middle_name'],
                'last_name'            => $data['last_name'],
                'prefix'               => $data['prefix'],
                'suffix'               => $data['suffix'] ?? null,
                'length_of_service'    => $data['length_of_service'] ?? '',
                // Required NOT NULL json column with no DB default — manual
                // registration doesn't collect this yet, so seed an empty list.
                'education_attainment' => json_encode([]),
            ]);

            if ($data['role'] === 'employee') {
                EmployeeProfile::insert([
                    'profile_id'          => $profileId,
                    'position'            => $data['position'],
                    'status'              => $data['status'],
                    // Required NOT NULL json column with no DB default — manual
                    // registration doesn't collect this yet, so seed an empty list.
                    'work_specification'  => json_encode([]),
                ]);
            }

            DB::commit();
            ActivityLogHelper::createUser($user);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e->withMessages([
                'message'    => 'There was an error while registering the new user.',
                'code_error' => $e->getMessage(),
            ]);
        }

        return response()->json(['message' => 'New user registered successfully.']);
    }

    public function admin_store(AdminRegistrationRequest $request)
    {
        $data = $request->validated();
        User::create([
            'role'             => $data['role'],
            'email'            => $data['email'],
            'username'         => $data['username'],
            'password'         => $data['password'],
        ]);

        return response()->json(['message' => 'New admin registered successfully.']);
    }

    // ── CSV Verify → Review → Commit ──────────────────────────────

    public function verify_csv(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $rows = $this->parse_csv($request->file('csv_file'));

        if (empty($rows)) {
            return response()->json(['message' => 'The CSV file is empty or could not be parsed.'], 422);
        }

        // Run verification synchronously — fast enough (just DB lookups, no heavy I/O)
        $existingEmails  = User::pluck('email')->flip();
        $existingDostIds = User::pluck('dost_employee_id')->filter()->flip();
        $seenEmails  = [];
        $seenDostIds = [];
        $results     = [];

        foreach ($rows as $i => $row) {
            $firstName       = trim($row['first_name']        ?? '');
            $middleName      = trim($row['middle_name']       ?? '');
            $prefix          = trim($row['prefix']            ?? '');
            $firstName       = trim($row['first_name']        ?? '');
            $middleName      = trim($row['middle_name']       ?? '');
            $lastName        = trim($row['last_name']         ?? '');
            $suffix          = trim($row['suffix']            ?? '');
            $email           = trim($row['email']             ?? '');
            $dostId          = trim($row['dost_id_number']    ?? $row['dost_employee_id'] ?? '');
            $position        = trim($row['position']          ?? '');
            $lengthOfService = trim($row['length_of_service'] ?? '');

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
                'row_index' => $i,
                'status'    => empty($errors) ? 'valid' : 'invalid',
                'errors'    => $errors,
                'include'   => empty($errors),
                'data'      => [
                    'prefix'            => $prefix,
                    'first_name'        => $firstName,
                    'middle_name'       => $middleName,
                    'last_name'         => $lastName,
                    'suffix'            => $suffix,
                    'email'             => $email,
                    'dost_id'           => $dostId,
                    'position'          => $position,
                    'length_of_service' => $lengthOfService,
                ],
            ];
        }

        return response()->json([
            'status'  => 'done',
            'results' => $results,
            'total'   => count($results),
        ]);
    }

    public function verify_status(string $key)
    {
        $data = Cache::get($key);
        abort_if(!$data, 404, 'Verification session expired or not found.');
        return response()->json($data);
    }

    public function commit_csv(Request $request)
    {
        $request->validate([
            'rows'                     => ['required', 'array', 'min:1'],
            'rows.*.first_name'        => ['required', 'string'],
            'rows.*.last_name'         => ['required', 'string'],
            'rows.*.email'             => ['required', 'email'],
            'rows.*.dost_id'           => ['required', 'string'],
            'rows.*.prefix'            => ['nullable', 'string'],
            'rows.*.suffix'            => ['nullable', 'string'],
            'rows.*.position'          => ['nullable', 'string'],
            'rows.*.length_of_service' => ['nullable', 'string'],
        ]);

        $actor        = Auth::user();
        $actor->loadMissing('provinces');
        $provinceId   = $actor->province_id;
        $provinceName = $actor->province?->name ?? 'Province';

        $jobs = array_map(fn($row) => new GenerateEmployeeAccount([
            'prefix'            => $row['prefix']            ?? '',
            'first_name'        => $row['first_name'],
            'middle_name'       => $row['middle_name']       ?? '',
            'last_name'         => $row['last_name'],
            'suffix'            => $row['suffix']            ?? '',
            'email'             => $row['email'],
            'dost_employee_id'  => $row['dost_id'],
            'position'          => $row['position']          ?? '',
            'length_of_service' => $row['length_of_service'] ?? '0',
        ], $provinceId), $request->rows);

        $batch = Bus::batch($jobs)
            ->name("Commit accounts — {$provinceName}")
            ->allowFailures()
            ->finally(function (Batch $batch) use ($provinceName) {
                $processed = $batch->totalJobs - $batch->failedJobs;
                ActivityLogHelper::generateAccounts($processed, $provinceName);
            })
            ->dispatch();

        return response()->json([
            'batch_id'   => $batch->id,
            'total_jobs' => $batch->totalJobs,
        ]);
    }

    // ── CSV bulk account generation (legacy direct) ────────────────

    public function upload_user_csv_file(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $actor        = Auth::user();
        $actor->loadMissing('provinces');
        $provinceId   = $actor->province_id;
        $provinceName = $actor->province?->name ?? 'Province';

        $rows = $this->parse_csv($request->file('csv_file'));

        if (empty($rows)) {
            return response()->json(['message' => 'The CSV file is empty or could not be parsed.'], 422);
        }

        $jobs = array_map(
            fn($row) => new GenerateEmployeeAccount($row, $provinceId),
            $rows
        );

        $batch = Bus::batch($jobs)
            ->name("Generate accounts — {$provinceName}")
            ->allowFailures()
            ->finally(function (Batch $batch) use ($provinceName) {
                $processed = $batch->totalJobs - $batch->failedJobs;
                ActivityLogHelper::generateAccounts($processed, $provinceName);
            })
            ->dispatch();

        return response()->json([
            'batch_id'   => $batch->id,
            'total_jobs' => $batch->totalJobs,
            'message'    => 'Account generation started.',
        ]);
    }

    public function batch_status(string $batchId)
    {
        $batch = Bus::findBatch($batchId);

        abort_if(!$batch, 404, 'Batch not found.');

        return response()->json([
            'id'             => $batch->id,
            'total_jobs'     => $batch->totalJobs,
            'pending_jobs'   => $batch->pendingJobs,
            'failed_jobs'    => $batch->failedJobs,
            'processed_jobs' => $batch->processedJobs(),
            'progress'       => $batch->progress(),
            'finished'       => $batch->finished(),
            'cancelled'      => $batch->cancelled(),
            'finished_at'    => $batch->finishedAt?->format('M d, Y g:i A'),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function get_users(?string $search = null, ?string $role = null)
    {
        $data = User::has('profile')
                    ->with(['profile', 'provinces.region', 'region'])
                    ->when($search, function ($q, $search) {
                        $q->where(function ($q) use ($search) {
                            $q->whereHas('profile', fn($p) =>
                                $p->whereRaw("CONCAT(first_name, ' ', COALESCE(middle_name,''), ' ', last_name) LIKE ?", ["%{$search}%"])
                            )
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('dost_employee_id', 'like', "%{$search}%");
                        });
                    })
                    ->when($role, fn($q, $role) => $q->where('role', $role));

        $actor = auth()->user();

        if ($actor->role === 'regional_admin') {
            $region = Region::with('provinces')->findOrFail($actor->region_id);
            $data->whereProvinceIn($region->provinces->pluck('id')->toArray());
        } elseif ($actor->province_id) {
            $data->whereProvince($actor->province_id);
        }

        $data = $data->latest('created_at')
                    ->paginate(20)
                    ->withQueryString();

        return UserResource::collection($data);
    }

    private function parse_csv(UploadedFile $file): array
    {
        $rows    = [];
        $headers = null;
        $handle  = fopen($file->getRealPath(), 'r');

        while (($line = fgetcsv($handle)) !== false) {
            if (!$headers) {
                $headers = array_map(
                    fn($h) => strtolower(trim(str_replace(' ', '_', $h))),
                    $line
                );
                continue;
            }
            if (count($line) !== count($headers)) continue;
            $row = array_combine($headers, $line);
            if (!empty(array_filter($row))) {
                $rows[] = $row;
            }
        }

        fclose($handle);
        return $rows;
    }

    private function get_role(): string
    {
        return match (Auth::user()->role) {
            'super_admin'      => 'Admin',
            'provincial_admin' => 'ProvincialAdmin',
            default            => 'Admin',
        };
    }
}
