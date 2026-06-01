<?php

namespace App\Http\Controllers\Modules\User;

use App\Helpers\ActivityLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\AdminRegistrationRequest;
use App\Http\Requests\User\UserRegistrationRequest;
use App\Http\Resources\UserResource;
use App\Jobs\GenerateEmployeeAccount;
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
    public function index()
    {
        return inertia('Admin/Users/Main', [
            'users' => self::get_users(),
        ]);
    }

    public function admin_index()
    {
        $admins = User::with(['profile', 'province'])
                      ->whereIn('role', ['super_admin', 'sub_admin', 'provincial_admin', 'provincial_sub_admin'])
                      ->latest('created_at')
                      ->paginate(20);

        return inertia('Admin/Users/Admins', [
            'admins' => UserResource::collection($admins),
        ]);
    }

    public function manual_registration_index()
    {
        $role = self::get_role();
        return inertia("$role/Users/Register/Manual");
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

        DB::beginTransaction();
        try {
            $user = User::create([
                'role'             => $data['role'],
                'province_id'      => $data['province'],
                'dost_employee_id' => $data['dost_employee_id'],
                'email'            => $data['email'],
                'username'         => $data['username'],
                'password'         => $data['password'],
            ]);

            $profileId = Profile::insertGetId([
                'user_id'     => $user->id,
                'first_name'  => $data['first_name'],
                'middle_name' => $data['middle_name'],
                'last_name'   => $data['last_name'],
                'prefix'      => $data['prefix'],
                'suffix'      => $data['suffix'],
            ]);

            if ($data['role'] === 'employee') {
                EmployeeProfile::insert([
                    'profile_id' => $profileId,
                    'position'   => $data['position'],
                    'status'     => $data['status'],
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
            'province_id'      => $data['province_id'],
            'email'            => $data['email'],
            'username'         => $data['username'],
            'password'         => $data['password'],
        ]);

        return response()->json(['message' => 'New admin registered successfully.']);
    }

    // ── CSV bulk account generation ────────────────────────────────

    public function upload_user_csv_file(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $actor        = Auth::user();
        $actor->loadMissing('province');
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

    public function get_users()
    {
        return UserResource::collection(
            User::has('profile')->with('profile')->latest('created_at')->paginate(20)
        );
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
