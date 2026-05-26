<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\User;

class EmployeeController extends Controller
{
    public function index() {
        return inertia('Other/Employees/Main', [
            'employees' => self::get_employees()
        ]);
    }

    public function get_employees() {
        $user = auth()->user();
        $data = User::has('profile.employeeProfile')
                    ->with(['province', 'profile.employeeProfile'])
                    ->where('role', 'employee')
                    ->when($user->role !== 'sub_admin', function ($query) use ($user) {
                        $query->where('province_id', $user->province_id);
                    })
                    ->latest('created_at')
                    ->paginate(20);
        
        return EmployeeResource::collection($data);
    }
}
