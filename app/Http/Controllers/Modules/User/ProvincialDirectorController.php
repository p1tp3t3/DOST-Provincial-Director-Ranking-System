<?php

namespace App\Http\Controllers\Modules\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\DirectorResource;
use App\Models\User;
use Illuminate\Http\Request;

class ProvincialDirectorController extends Controller
{
    public function index() {
        $data = User::has('profile')
                    ->with(['province', 'profile'])
                    ->where('role', 'provincial_director')
                    ->paginate(12);

        return inertia('Other/Director/Main', [
            'directors' => DirectorResource::collection($data)
        ]);
    }
}
