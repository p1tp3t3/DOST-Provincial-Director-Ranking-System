<?php

use App\Http\Controllers\Auth\SuperAdminLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Modules\MaintenanceController;
use App\Http\Controllers\Modules\User\EmployeeController;
use App\Http\Controllers\Modules\ProvinceController;
use App\Http\Controllers\Modules\Report\SuperAdminReportController;
use App\Http\Controllers\Modules\Report\ProvincialAdminReportController;
use App\Http\Controllers\Modules\Report\SubAdminReportController;
use App\Http\Controllers\Modules\User\ActivityLogController;
use App\Http\Controllers\Modules\Report\ProvincialSubAdminReportController;
use App\Http\Controllers\Modules\User\ProvincialDirectorController;
use App\Http\Controllers\Modules\User\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ── Public: maintenance notice ─────────────────────────────────
Route::get('/maintenance-notice', fn() => inertia('Other/Maintenance/Main'))->name('maintenance-notice');

// ── Super admin console login (bypasses maintenance mode) ──────
Route::middleware('guest')->group(function () {
    Route::get('/console/login',        [SuperAdminLoginController::class, 'show'])->name('console.login');
    Route::post('/console/authenticate', [SuperAdminLoginController::class, 'store'])->name('console.authenticate');
});

Route::get('/', function () {
    return Inertia::render('Landing/Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {

    // ── All authenticated users ────────────────────────────────
    Route::get('/dashboard',       [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('profile-view')->group(function () {

        Route::get('/profile/{id}', [ProfileController::class, 'index']);
        Route::get('/profile-picture', [ProfileController::class, 'get_profile_picture'])->name('profile.picture');
        Route::get('/profile',         [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile',       [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/picture',[ProfileController::class, 'update_picture'])->name('profile.picture.update');
        Route::delete('/profile',      [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    });

    Route::get('/province-directories',       [ProvinceController::class, 'index']);
    Route::post('/province-directories/add',  [ProvinceController::class, 'store']);
    Route::get('/province-directories/{id}',  [ProvinceController::class, 'province_profile_index']);
    Route::get('/provincial-directors',       [ProvincialDirectorController::class, 'index']);

    // ── Super Admin only ───────────────────────────────────────
    Route::middleware('super-admin')->group(function () {
        Route::get('/maintenance',                                    [MaintenanceController::class, 'index']);
        Route::post('/maintenance/backup',                            [MaintenanceController::class, 'create_backup']);
        Route::get('/maintenance/backup/{filename}',                  [MaintenanceController::class, 'download_backup']);
        Route::delete('/maintenance/backup/{filename}',               [MaintenanceController::class, 'delete_backup']);
        Route::post('/maintenance/storage-backup',                    [MaintenanceController::class, 'create_storage_backup']);
        Route::get('/maintenance/storage-backup/{filename}',          [MaintenanceController::class, 'download_storage_backup']);
        Route::delete('/maintenance/storage-backup/{filename}',       [MaintenanceController::class, 'delete_storage_backup']);
        Route::post('/maintenance/system-backup',                     [MaintenanceController::class, 'create_system_backup']);
        Route::get('/maintenance/system-backup/{filename}',           [MaintenanceController::class, 'download_system_backup']);
        Route::delete('/maintenance/system-backup/{filename}',        [MaintenanceController::class, 'delete_system_backup']);
        Route::post('/maintenance/cache/clear/{key}',                 [MaintenanceController::class, 'clear_cache']);
        Route::post('/maintenance/optimize',                          [MaintenanceController::class, 'optimize']);
        Route::post('/maintenance/reset',                             [MaintenanceController::class, 'reset']);
        Route::post('/maintenance/toggle-mode',                       [MaintenanceController::class, 'toggle_maintenance']);

        Route::get('/super-admin-report',        [SuperAdminReportController::class, 'index']);
        Route::get('/super-admin-report/export', [SuperAdminReportController::class, 'export']);

        Route::get('/admins', [UserController::class, 'admin_index']);
    });

    // ── Super Admin + Provincial Admin ─────────────────────────
    Route::middleware('role:super_admin,provincial_admin')->group(function () {
        Route::get('/users',                                           [UserController::class, 'index']);
        Route::get('/activity-logs',                                   [ActivityLogController::class, 'index']);
        Route::get('/activity-logs/report',                            [ActivityLogController::class, 'generate_logs_report']);

        Route::get('/users/create',                                    [UserController::class, 'manual_registration_index']);
        Route::post('/users/store',                                    [UserController::class, 'store']);
        Route::get('/users/auto-generator',                            [UserController::class, 'auto_registration_index']);
        Route::post('/provincial-admin/auto-generator/generate',       [UserController::class, 'upload_user_csv_file']);
        Route::get('/auto-generator/batch/{batchId}',                  [UserController::class, 'batch_status']);
        Route::post('/provincial-admin/csv/verify',                    [UserController::class, 'verify_csv']);
        Route::get('/provincial-admin/csv/verify/{key}',               [UserController::class, 'verify_status']);
        Route::post('/provincial-admin/csv/commit',                    [UserController::class, 'commit_csv']);
    });

    // ── Sub Admin only ─────────────────────────────────────────
    Route::middleware('sub-admin')->group(function () {
        Route::get('/sub-admin-report',        [SubAdminReportController::class, 'index']);
        Route::get('/sub-admin-report/export', [SubAdminReportController::class, 'export']);
    });
    
    Route::middleware('role:super_admin,sub_admin')->group(function () {
        Route::get('/performance-map', [DashboardController::class, 'map_index'])->name('performance-map');
    });

    // ── Provincial Admin only ─────────────────────────────────
    Route::middleware('role:provincial_admin')->group(function () {
        Route::get('/provincial-admin-report',        [ProvincialAdminReportController::class, 'index']);
        Route::get('/provincial-admin-report/export', [ProvincialAdminReportController::class, 'export']);
    });

    // ── Provincial Sub Admin + Provincial Admin ────────────────
    Route::middleware('role:provincial_sub_admin,provincial_admin')->group(function () {
        Route::get('/provincial-sub-admin-report',        [ProvincialSubAdminReportController::class, 'index']);
        Route::get('/provincial-sub-admin-report/export', [ProvincialSubAdminReportController::class, 'export']);
    });

    // ── Sub Admin + Provincial Admin + Provincial Sub Admin + Provincial Director ──
    Route::middleware('role:sub_admin,provincial_admin,provincial_sub_admin,provincial_director')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index']);
    });
});

require __DIR__.'/auth.php';
