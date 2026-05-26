<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        switch(auth()->user()->role) {
            case 'super_admin':
                return self::super_admin_dashboard();
            case 'sub_admin':
                return self::sub_admin_dashboard();
            case 'provincial_admin':
                return self::provincial_admin_dashboard();
            case 'provincial_director':
                return self::director_dashboard();
            case 'employee':
                return self::employee_dashboard();
        }
    }


    private function super_admin_dashboard() 
    {
        return inertia('Admin/Dashboard/Main', [
            'total_provinces' => Province::count(),
            'total_users' => User::count(),
            'total_directors' => User::where('role', 'provincial_director')->count(),
            'total_employees' => User::where('role', 'employee')->count()
        ]);
    }

    private function sub_admin_dashboard() 
    {
        return inertia('SubAdmin/Dashboard/Main', [
            'total_directors' => User::where('role', 'provincial_director')->count(),
            'total_employees' => User::where('role', 'employee')->count(),
            'total_provinces' => Province::count()
        ]);
    }

    private function provincial_admin_dashboard() 
    {
        return inertia('ProvincialAdmin/Dashboard/Main', [
            'total_employees' => User::where('role', 'employee')
                                     ->where('province_id', auth()->user()->province_id)
                                     ->count(),
        ]);
    }

    private function director_dashboard() 
    {
        return inertia('Director/Dashboard/Main', [
            'total_employees' => User::where('role', 'employee')
                                     ->where('province_id', auth()->user()->province_id)
                                     ->count(),
        ]);
    }

    private function employee_dashboard() {
        return inertia('Employee/Profile');
    }
}
