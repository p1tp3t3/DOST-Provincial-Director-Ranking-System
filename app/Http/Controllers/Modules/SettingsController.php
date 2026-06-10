<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $user    = Auth::user()->load(['profile', 'province']);
        $profile = $user->profile;

        return inertia('Other/Settings/Main', [
            'account' => [
                'username'   => $user->username,
                'email'      => $user->email,
                'role'       => $user->role,
                'province'   => $user->province?->name,
                'employee_id'=> $user->dost_employee_id,
                'name'       => collect([
                    $profile?->prefix,
                    $profile?->first_name,
                    $profile?->middle_name,
                    $profile?->last_name,
                    $profile?->suffix,
                ])->filter()->implode(' '),
            ],
        ]);
    }
}
