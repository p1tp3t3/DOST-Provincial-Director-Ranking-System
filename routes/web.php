<?php

use App\Http\Controllers\Auth\SuperAdminLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Modules\FacebookPostController;
use App\Http\Controllers\Modules\KPIDataController;
use App\Http\Controllers\Modules\KpiEditAccessController;
use App\Http\Controllers\Modules\LinkageController;
use App\Http\Controllers\Modules\MaintenanceController;
use App\Http\Controllers\Modules\User\EmployeeController;
use App\Http\Controllers\Modules\ProvinceController;
use App\Http\Controllers\Modules\Report\SuperAdminReportController;
use App\Http\Controllers\Modules\Report\ProvincialAdminReportController;
use App\Http\Controllers\Modules\Report\SubAdminReportController;
use App\Http\Controllers\Modules\User\ActivityLogController;
use App\Http\Controllers\Modules\Report\ProvincialSubAdminReportController;
use App\Http\Controllers\Modules\Report\RegionalAdminReportController;
use App\Http\Controllers\Modules\User\ProvincialDirectorController;
use App\Http\Controllers\Modules\User\UserController;
use App\Http\Controllers\Modules\SettingsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ── Public: landing page ───────────────────────────────────────
Route::get('/', fn() => inertia('Landing/Welcome', [
    'canLogin'    => Route::has('login'),
    'canRegister' => Route::has('register'),
]))->name('home');

// ── Public: interactive map ────────────────────────────────────
Route::get('/map', fn() => inertia('Landing/Map', [
    'canLogin' => Route::has('login'),
]))->name('map');

// ── Public: blog detail ────────────────────────────────────────
Route::get('/blog/{slug}', fn(string $slug) => inertia('Landing/Blog', [
    'slug'     => $slug,
    'canLogin' => Route::has('login'),
]))->name('blog.show');

// ── Public: maintenance notice ─────────────────────────────────
Route::get('/maintenance-notice', fn() => inertia('Other/Maintenance/Main'))->name('maintenance-notice');

// ── Super admin console login (bypasses maintenance mode) ──────
Route::middleware('guest')->group(function () {
    Route::get('/console/login',        [SuperAdminLoginController::class, 'show'])->name('console.login');
    Route::post('/console/authenticate', [SuperAdminLoginController::class, 'store'])->name('console.authenticate');
});


Route::middleware(['auth', 'activation'])->group(function () {

    // ── All authenticated users ────────────────────────────────
    Route::get('/dashboard',       [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings',        [SettingsController::class, 'index'])->name('settings');

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
        Route::put('/users/{id}', [UserController::class, 'update']);

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

    // ── KPI Data Editor: Super Admin + Sub Admin ───────────────
    Route::middleware('role:super_admin,sub_admin')->group(function () {
        Route::get('/kpi-data',                   [KPIDataController::class, 'index']);
        Route::get('/kpi-data/{id}/{year?}',      [KPIDataController::class, 'edit']);
        Route::put('/kpi-data/{director}/{year}', [KPIDataController::class, 'update']);
    });

    // ── Activity Logs: Super Admin + Provincial Admin + Regional Admin ────
    Route::middleware('role:super_admin,provincial_admin,regional_admin')->group(function () {
        Route::get('/activity-logs',        [ActivityLogController::class, 'index']);
        Route::get('/activity-logs/report', [ActivityLogController::class, 'generate_logs_report']);
    });

    // Read-only user list also available to Sub Admin (nationwide) and Regional
    // Admin (scoped to their own region); management actions below stay
    // restricted to Super Admin/Provincial Admin.
    Route::middleware('role:super_admin,sub_admin,provincial_admin,regional_admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
    });

    // ── Super Admin + Provincial Admin ─────────────────────────
    Route::middleware('role:super_admin,provincial_admin')->group(function () {
        Route::delete('/users/{id}',                                   [UserController::class, 'destroy']);
        Route::patch('/users/{id}/toggle-activation',                  [UserController::class, 'toggle_activation']);

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

    // ── Regional Admin only ─────────────────────────────────────
    Route::middleware('role:regional_admin')->group(function () {
        Route::get('/regional-performance-map', [DashboardController::class, 'regional_map_index'])->name('regional-performance-map');
        Route::get('/regional-admin-report',        [RegionalAdminReportController::class, 'index']);
        Route::get('/regional-admin-report/export', [RegionalAdminReportController::class, 'export']);

        // KPI edit access requests — approve/reject a provincial admin's request
        // for an extra edit once their free edit for a year/type is spent.
        Route::get('/regional-kpi-edit-requests',                [KpiEditAccessController::class, 'index']);
        Route::patch('/regional-kpi-edit-requests/{id}/approve', [KpiEditAccessController::class, 'approve']);
        Route::patch('/regional-kpi-edit-requests/{id}/reject',  [KpiEditAccessController::class, 'reject']);
    });

    // ── Provincial Admin only ─────────────────────────────────
    Route::middleware('role:provincial_admin')->group(function () {
        Route::get('/provincial-admin-report',        [ProvincialAdminReportController::class, 'index']);
        Route::get('/provincial-admin-report/export', [ProvincialAdminReportController::class, 'export']);
    });

    // ── Provincial Admin only (KPI editor) ────────────────────
    Route::middleware('role:provincial_admin')->group(function () {
        Route::get('/provincial-kpi/{year?}',                            [KPIDataController::class, 'provincial_index']);
        Route::put('/provincial-kpi/{director}/{year}',                  [KPIDataController::class, 'provincial_update']);
        Route::post('/provincial-kpi/{director}/{year}/request-access', [KPIDataController::class, 'provincial_request_access']);
    });

    // ── Evidence-based KPI modules (Super Admin + Provincial Sub Admin + Provincial Admin) ──
    // Each module backs a specific KPI. The accomplishment field for that KPI
    // is locked and derived from the count of records here.
    // Union of main (provincial_sub_admin) + pete's change (provincial_admin) so
    // neither role loses access during the merge.
    Route::middleware('role:super_admin,provincial_sub_admin,provincial_admin')->group(function () {
        // Linkages → func_linkages_established
        Route::get('/linkages/{director}/{year}',                 [LinkageController::class, 'index']);
        Route::post('/linkages/{director}/{year}',                [LinkageController::class, 'store']);
        Route::put('/linkages/{director}/{year}/{linkage}',       [LinkageController::class, 'update']);
        Route::delete('/linkages/{director}/{year}/{linkage}',    [LinkageController::class, 'destroy']);

        // Facebook Posts → supp_facebook_posts
        Route::get('/facebook-posts/{director}/{year}',              [FacebookPostController::class, 'index']);
        Route::post('/facebook-posts/{director}/{year}',             [FacebookPostController::class, 'store']);
        Route::put('/facebook-posts/{director}/{year}/{post}',       [FacebookPostController::class, 'update']);
        Route::delete('/facebook-posts/{director}/{year}/{post}',    [FacebookPostController::class, 'destroy']);
    });

    // ── Provincial Sub Admin + Provincial Admin ────────────────
    Route::middleware('role:provincial_sub_admin,provincial_admin')->group(function () {
        Route::get('/provincial-sub-admin-report',        [ProvincialSubAdminReportController::class, 'index']);
        Route::get('/provincial-sub-admin-report/export', [ProvincialSubAdminReportController::class, 'export']);
    });

    Route::middleware('role:provincial_director')->group(function () {
        Route::get('/provincial-director-report',        [ProvincialSubAdminReportController::class, 'index']);
        Route::get('/provincial-director-report/export', [ProvincialSubAdminReportController::class, 'export']);
    });

    // ── Sub Admin + Provincial Admin + Provincial Director ─────
    Route::middleware('role:sub_admin,provincial_admin,provincial_director')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index']);
    });
});

require __DIR__.'/auth.php';
