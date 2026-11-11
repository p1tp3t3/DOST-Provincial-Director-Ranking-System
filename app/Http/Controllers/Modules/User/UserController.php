<?php

namespace App\Http\Controllers\Modules\User;

use App\Helpers\ActivityLogHelper;
use App\Helpers\XlsxHelper;
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
            'province_id'           => ['nullable', 'exists:provinces,id'],
            'dost_employee_id'      => ['required', 'string', 'max:50', 'unique:users,dost_employee_id,' . $id],
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

        // Moving an active provincial director into another province must not
        // give that province a second active director.
        $newProvinceId = !empty($validated['province_id']) ? (int) $validated['province_id'] : null;
        if ($newProvinceId && $newProvinceId !== $user->province_id
            && $user->activate && $user->hasLockedRole('provincial_director')) {
            try {
                $user->assertDirectorSlotFree('provincial_director', $newProvinceId);
            } catch (ValidationException $e) {
                throw ValidationException::withMessages(['province_id' => $e->getMessage()]);
            }
        }

        DB::beginTransaction();
        try {
            $userFields = [
                'dost_employee_id' => $validated['dost_employee_id'],
                'email'            => $validated['email'],
            ];

            if (!empty($validated['password'])) {
                $userFields['password'] = $validated['password'];
            }

            $user->update($userFields);

            $user->provinces()->sync($newProvinceId ? [$newProvinceId] : []);

            // Optional fields left blank are saved as blank, never as a missing
            // value; length of service can't be null in the database. An account
            // without a profile row yet gets one instead of failing the save.
            $user->profile()->updateOrCreate(['user_id' => $user->id], [
                'prefix'            => $validated['prefix']            ?? null,
                'first_name'        => $validated['first_name'],
                'middle_name'       => $validated['middle_name']       ?? null,
                'last_name'         => $validated['last_name'],
                'suffix'            => $validated['suffix']            ?? null,
                'length_of_service' => $validated['length_of_service'] ?? '',
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

        // Re-activating a director would put them back in their province/region
        // slot — refuse if someone else is already the active director there.
        if (!$user->activate) {
            foreach ($user->lockedDirectorRoles() as $roleName) {
                $user->assertDirectorSlotFree($roleName);
            }
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
            // The province's KPI figures and evidence records are kept: they
            // belong to the province, and their director link is nulled by the
            // database on delete.
            ActivityLog::where('user_id', $user->id)->delete();
            $user->notifications()->delete();
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

        $admins = User::with(['profile', 'provinces', 'region', 'roles', 'activeRole'])
                      ->whereRoleIn(['super_admin', 'sub_admin', 'regional_director', 'provincial_admin'])
                      ->when($search, function ($q, $search) {
                          $q->where(function ($q) use ($search) {
                              $q->whereHas('profile', fn($p) =>
                                  $p->whereRaw("CONCAT(first_name, ' ', COALESCE(middle_name,''), ' ', last_name) LIKE ?", ["%{$search}%"])
                              )
                              ->orWhere('email', 'like', "%{$search}%")
                              ->orWhere('dost_employee_id', 'like', "%{$search}%");
                          });
                      })
                      ->when($role, fn($q, $role) => $q->whereRole($role))
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
        // One generator for both: the provincial admin generates into their own
        // province; the super admin selects which province the list belongs to.
        return inertia('ProvincialAdmin/Users/Register/AutoGenerator', [
            'provinces' => Auth::user()->role === 'provincial_admin'
                ? []
                : Province::orderBy('name')->get(['id', 'name']),
        ]);
    }

    // ── Manual registration ────────────────────────────────────────

    public function store(UserRegistrationRequest $request)
    {
        $data      = $request->validated();
        $actor     = Auth::user();
        $adminRole = $request->adminRole(); // optional admin role on top of employee

        DB::beginTransaction();
        try {
            // Provincial admins can only register into their own province — never
            // trust a client-submitted value for them, even if one were sent.
            $provinceId = $actor->role === 'provincial_admin' ? $actor->province_id : ($data['province'] ?? null);
            $province   = Province::findOrFail($provinceId);

            $user = User::create([
                'dost_employee_id' => $data['dost_employee_id'],
                'email'            => $data['email'],
                'password'         => $data['password'],
                // A regional director's region is the region of their province.
                'region_id'        => $adminRole === 'regional_director' ? $province->region_id : null,
            ]);

            // Province first, so assignRole()'s one-director-per-province check sees it.
            $user->provinces()->attach($province->id);

            // Every user is an employee. An admin role is assigned first so it's
            // the role they land in; assignRole() adds employee underneath it.
            $user->assignRole($adminRole ?? 'employee');

            $profileId = Profile::insertGetId([
                'user_id'              => $user->id,
                'first_name'           => $data['first_name'],
                'middle_name'          => $data['middle_name'] ?? null,
                'last_name'            => $data['last_name'],
                'prefix'               => $data['prefix'],
                'suffix'               => $data['suffix'] ?? null,
                'length_of_service'    => $data['length_of_service'] ?? '',
                // Required NOT NULL json column with no DB default — manual
                // registration doesn't collect this yet, so seed an empty list.
                'education_attainment' => json_encode([]),
            ]);

            if (!empty($data['position'])) {
                EmployeeProfile::insert([
                    'profile_id'          => $profileId,
                    'position'            => $data['position'],
                    'status'              => $data['status'],
                    // Required NOT NULL json column with no DB default — manual
                    // registration doesn't collect this yet, so seed an empty list.
                    'work_specification'  => json_encode([]),
                ]);
            }
            // Every user is an employee — when no position/status was asked
            // (provincial admin registering a director), add a default record.
            $user->ensureEmployeeProfile();

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
        $user = User::create([
            'dost_employee_id' => $data['dost_employee_id'],
            'email'            => $data['email'],
            'password'         => $data['password'],
        ]);
        $user->assignRole($data['role']);
        $user->ensureEmployeeProfile();

        return response()->json(['message' => 'New admin registered successfully.']);
    }

    // ── CSV Verify → Review → Commit ──────────────────────────────

    public function verify_csv(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        try {
            $rows = $this->parse_upload($request->file('csv_file'));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (empty($rows)) {
            return response()->json(['message' => 'The file is empty or could not be parsed.'], 422);
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
                $errors['dost_id'] = 'Employee no. is required.';
            } elseif (isset($existingDostIds[$dostId])) {
                $errors['dost_id'] = 'Employee no. already exists in the system.';
            } elseif (isset($seenDostIds[$dostId])) {
                $errors['dost_id'] = 'Duplicate employee no. in this CSV.';
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
            // Optional admin role per row, chosen by the super admin in review.
            'rows.*.admin_role'        => ['nullable', 'in:' . implode(',', self::GENERATOR_ADMIN_ROLES)],
        ]);

        if (Auth::user()->role !== 'super_admin' && collect($request->rows)->contains(fn($r) => !empty($r['admin_role']))) {
            throw ValidationException::withMessages(['rows' => 'Only the super admin can assign admin roles.']);
        }

        // The province this list belongs to: the provincial admin's own, or the
        // one the super admin selected on the generator page.
        $province     = $this->generation_province($request);
        $provinceId   = $province->id;
        $provinceName = $province->name;

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
            'admin_role'        => ($row['admin_role'] ?? null) ?: null,
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

    // The province generated accounts belong to: a provincial admin's own
    // province (never a client-sent value), or the province the super admin
    // picked on the generator page.
    private function generation_province(Request $request): Province
    {
        $actor = Auth::user();
        if ($actor->role === 'provincial_admin') {
            $actor->loadMissing('provinces');
            return Province::findOrFail($actor->province_id);
        }

        $request->validate(['province_id' => ['required', 'exists:provinces,id']]);
        return Province::findOrFail($request->integer('province_id'));
    }

    // ── CSV bulk account generation (legacy direct) ────────────────

    public function upload_user_csv_file(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $province     = $this->generation_province($request);
        $provinceId   = $province->id;
        $provinceName = $province->name;

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
                    ->with(['profile', 'provinces.region', 'region', 'roles', 'activeRole'])
                    ->when($search, function ($q, $search) {
                        $q->where(function ($q) use ($search) {
                            $q->whereHas('profile', fn($p) =>
                                $p->whereRaw("CONCAT(first_name, ' ', COALESCE(middle_name,''), ' ', last_name) LIKE ?", ["%{$search}%"])
                            )
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('dost_employee_id', 'like', "%{$search}%");
                        });
                    })
                    ->when($role, fn($q, $role) => $q->whereRole($role));

        $actor = auth()->user();

        if ($actor->role === 'regional_director') {
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

    // Admin roles the super admin can give generated accounts (on top of employee).
    private const GENERATOR_ADMIN_ROLES = ['super_admin', 'sub_admin', 'provincial_admin'];

    // Columns of the account generator's template, in order.
    private const GENERATOR_COLUMNS = [
        'dost_id_number', 'prefix', 'first_name', 'middle_name', 'last_name',
        'suffix', 'email', 'position', 'length_of_service',
    ];

    // Excel template for the account generator: the columns above plus 10
    // sample employees to replace with real data.
    public function generator_template()
    {
        $rows = [
            ['DOST-YYYY-EMP-001', 'Mr.',   'Juan',   'Santos', 'Dela Cruz',  '',    'juan.delacruz@dost.gov.ph',   'Science Research Analyst',           '3'],
            ['DOST-YYYY-EMP-002', 'Ms.',   'Maria',  'Reyes',  'Villanueva', '',    'maria.villanueva@dost.gov.ph', 'Administrative Officer II',          '1'],
            ['DOST-YYYY-EMP-003', 'Engr.', 'Jose',   'Garcia', 'Mendoza',    'Jr.', 'jose.mendoza@dost.gov.ph',     'Engineer II',                        '6'],
            ['DOST-YYYY-EMP-004', 'Ms.',   'Ana',    'Lopez',  'Bautista',   '',    'ana.bautista@dost.gov.ph',     'Project Technical Assistant',        '2'],
            ['DOST-YYYY-EMP-005', 'Mr.',   'Carlo',  '',       'Ramos',      '',    'carlo.ramos@dost.gov.ph',      'Science Research Specialist I',      '4'],
            ['DOST-YYYY-EMP-006', 'Dr.',   'Elena',  'Torres', 'Aquino',     '',    'elena.aquino@dost.gov.ph',     'Senior Science Research Specialist', '10'],
            ['DOST-YYYY-EMP-007', 'Mr.',   'Paolo',  'Cruz',   'Navarro',    'III', 'paolo.navarro@dost.gov.ph',    'Information Systems Analyst I',      '2'],
            ['DOST-YYYY-EMP-008', 'Mrs.',  'Grace',  'Flores', 'Castillo',   '',    'grace.castillo@dost.gov.ph',   'Accountant II',                      '7'],
            ['DOST-YYYY-EMP-009', 'Mr.',   'Miguel', 'Lim',    'Fernandez',  '',    'miguel.fernandez@dost.gov.ph', 'Administrative Aide IV',             '5'],
            ['DOST-YYYY-EMP-010', 'Ms.',   'Liza',   'Gomez',  'Tan',        '',    'liza.tan@dost.gov.ph',         'Science Aide',                       '1'],
        ];

        $path = XlsxHelper::write(self::GENERATOR_COLUMNS, $rows, 'Employees');

        return response()->download($path, 'employee-accounts-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    // Rows from an uploaded .csv or .xlsx, keyed by normalized header.
    private function parse_upload(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'xlsx') return $this->parse_csv($file);

        $lines = XlsxHelper::read($file->getRealPath());
        if (!$lines) return [];

        $headers = array_map(fn($h) => $this->normalize_header($h), array_shift($lines));
        $rows = [];
        foreach ($lines as $line) {
            $line = array_pad(array_slice($line, 0, count($headers)), count($headers), '');
            $row  = array_combine($headers, $line);
            if (!empty(array_filter($row, fn($v) => trim((string) $v) !== ''))) $rows[] = $row;
        }
        return $rows;
    }

    // "First Name " / "\u{FEFF}dost_id_number" → "first_name" / "dost_id_number".
    private function normalize_header(string $h): string
    {
        $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
        return strtolower(str_replace(' ', '_', trim($h)));
    }

    private function parse_csv(UploadedFile $file): array
    {
        $rows    = [];
        $headers = null;
        $handle  = fopen($file->getRealPath(), 'r');

        while (($line = fgetcsv($handle)) !== false) {
            if (!$headers) {
                // normalize_header() also strips the byte-order mark Excel's
                // "CSV UTF-8" adds, so the first header (dost_id_number) matches.
                $headers = array_map(fn($h) => $this->normalize_header((string) $h), $line);
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
