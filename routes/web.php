<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Modules\MaintenanceController;
use App\Http\Controllers\Modules\User\EmployeeController;
use App\Http\Controllers\Modules\ProvinceController;
use App\Http\Controllers\Modules\Report\SuperAdminReportController;
use App\Http\Controllers\Modules\Report\SubAdminReportController;
use App\Http\Controllers\Modules\User\ActivityLogController;
use App\Http\Controllers\Modules\User\ProvincialDirectorController;
use App\Http\Controllers\Modules\User\UserController;
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
Route::get('/performance-map', [DashboardController::class, 'map_index'])->middleware(['auth', 'verified'])->name('performance-map');

Route::middleware('auth')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/admins', [UserController::class, 'admin_index']);
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    Route::get('/activity-logs/report', [ActivityLogController::class, 'generate_logs_report']);
    Route::get('/maintenance', [MaintenanceController::class, 'index']);
    Route::post('/maintenance/backup', [MaintenanceController::class, 'create_backup']);
    Route::get('/maintenance/backup/{filename}', [MaintenanceController::class, 'download_backup']);
    Route::delete('/maintenance/backup/{filename}', [MaintenanceController::class, 'delete_backup']);
    Route::post('/maintenance/cache/clear/{key}', [MaintenanceController::class, 'clear_cache']);
    Route::post('/maintenance/optimize', [MaintenanceController::class, 'optimize']);
    Route::post('/maintenance/reset', [MaintenanceController::class, 'reset']);

    Route::get('/users/create', [UserController::class, 'manual_registration_index']);
    Route::post('/users/store', [UserController::class, 'store']);
    Route::get('/users/auto-generator', [UserController::class, 'auto_registration_index']);
    Route::post('/provincial-admin/auto-generator/generate', [UserController::class, 'upload_user_csv_file']);
    Route::get('/auto-generator/batch/{batchId}', [UserController::class, 'batch_status']);
    Route::post('/provincial-admin/csv/verify', [UserController::class, 'verify_csv']);
    Route::get('/provincial-admin/csv/verify/{key}', [UserController::class, 'verify_status']);
    Route::post('/provincial-admin/csv/commit', [UserController::class, 'commit_csv']);
    Route::get('/super-admin-report', [SuperAdminReportController::class, 'index']);
    Route::get('/super-admin-report/export', [SuperAdminReportController::class, 'export']);

    Route::get('/sub-admin-report', [SubAdminReportController::class, 'index']);

    Route::get('/profile/{id}', [ProfileController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    

    Route::get('/province-directories', [ProvinceController::class, 'index']);
    Route::post('/province-directories/add', [ProvinceController::class, 'store']);
    Route::get('/province-directories/{id}', [ProvinceController::class, 'province_profile_index']);
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::get('/provincial-directors', [ProvincialDirectorController::class, 'index']);
});

require __DIR__.'/auth.php';
