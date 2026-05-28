<?php

namespace App\Http\Controllers\Modules\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserRegistrationRequest;
use App\Http\Resources\UserResource;
use App\Models\EmployeeProfile;
use App\Models\Profile;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        return inertia('Admin/Users/Main', [
            'users' => self::get_users()
        ]);
    }

    public function admin_index() {
        return inertia('Admin/Users/Admins', [
            'super_admins' => [],
            'sub_admins' => [],
            'provincial_admins' => []
        ]);
    }

    public function manual_registration_index() {
        return inertia("Admin/Users/Register/Manual");
    }

    public function auto_registration_index() {
        return inertia("Admin/Users/Register/AutoGenerator", [
            'provincial_admin_requests' => []
        ]);
    }

    //  function for the manual registration
    public function store(UserRegistrationRequest $request) {
        $data = $request->validated();
        
        DB::beginTransaction();
        try {
            $user = User::create([
                'role' => $data['role'],
                'province_id' => $data['province'],
                'dost_employee_id' => $data['dost_employee_id'],
                'email' => $data['email'],
                'username' => $data['username'],
                'password' => $data['password']
            ]);
            $profile = Profile::insertGetId([
                            'user_id' => $user->id,
                            'first_name' => $data['first_name'],
                            'middle_name' => $data['middle_name'],
                            'last_name' => $data['last_name'],
                            'prefix' => $data['prefix'],
                            'suffix' => $data['suffix'],
                        ]);
            if($data['role'] == 'employee') {
                EmployeeProfile::insert([
                    'profile_id' => $profile,
                    'position' => $data['position'],
                    'status' => $data['status']
                ]);
            }
            DB::commit();
        }catch(ValidationException $x) {
            throw $x->withMessages([
                'message' => 'there was an error while registering the new user',
                'code_error' => $x->getMessage(),
            ]);
        }

        return response()->json(['message' => 'new user has been registered successfully']);
    }

    public function status_auto_registration_request(UserRegistrationRequest $request) {
        $data = $request->validated();
        $status = $data['status'];

        if(!in_array($status, ['approved', 'rejected'])) 
            return ValidationException::withMessages([
                       'message' => 'status must only be approved or rejected. please try again'
                   ]);

        if($status == 'approved') {
            return self::auto_generate_provincial_members($request);
        }
        else if($status == 'rejected') {
            return self::reject_auto_registration_request($request);
        }
    }


    //  function for the auto registration
    private function auto_generate_provincial_members($data) {
        /*  if approve
            - validate the request like the csv file
            - validate the content of the csv file
            - put it in a job batch
            - notify the provincial admin request status
            - return success and wait for the accounts to be generated
        */ 
        return;
    }

    //  function for the rejection of the registration request
    private function reject_auto_registration_request($data) {
        /*  if reject
            - notify the provincial admin request status
            - return success
        */ 
        return;
    }


    public function get_users()
    {
        $data = User::has('profile')
                    ->with('profile')
                    ->latest('created_at')
                    ->paginate(20);

         return UserResource::collection($data);
    }
}
