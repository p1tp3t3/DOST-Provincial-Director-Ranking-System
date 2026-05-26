<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return inertia('Admin/Users/Main', [
            'users' => self::get_users()
        ]);
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
