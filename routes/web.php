<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Modules\EmployeeController;
use App\Http\Controllers\Modules\ProvinceController;
use App\Http\Controllers\Modules\ProvincialDirectorController;
use App\Http\Controllers\Modules\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing/Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/users', [UserController::class, 'index']);

    Route::get('/profile/{id}', [ProfileController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    

    Route::get('/province-directories', [ProvinceController::class, 'index']);
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::get('/provincial-directors', [ProvincialDirectorController::class, 'index']);
});

require __DIR__.'/auth.php';
