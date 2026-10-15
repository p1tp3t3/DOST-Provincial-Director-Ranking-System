<?php

use App\Http\Controllers\MaintenanceNoticeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\Auth\RoleSwitchController;
use App\Http\Controllers\Auth\SuperAdminLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Modules\BlogController;
use App\Http\Controllers\Modules\FacebookPostController;
use App\Http\Controllers\Modules\KPICatalogController;
use App\Http\Controllers\Modules\KpiCatalogSnapshotController;
use App\Http\Controllers\Modules\KPIDataController;
use App\Http\Controllers\Modules\KpiDeadlineController;
use App\Http\Controllers\Modules\KpiEntryController;
use App\Http\Controllers\Modules\LinkageController;
use App\Http\Controllers\Modules\MaintenanceController;
use App\Http\Controllers\Modules\User\EmployeeController;
use App\Http\Controllers\Modules\ProvinceController;
use App\Http\Controllers\Modules\RegionController;
use App\Http\Controllers\Modules\Report\SuperAdminReportController;
use App\Http\Controllers\Modules\Report\ProvincialAdminReportController;
use App\Http\Controllers\Modules\Report\SubAdminReportController;
use App\Http\Controllers\Modules\User\ActivityLogController;
use App\Http\Controllers\Modules\Report\ProvincialSubAdminReportController;
use App\Http\Controllers\Modules\Report\RegionalDirectorReportController;
use App\Http\Controllers\Modules\User\ProvincialDirectorController;
use App\Http\Controllers\Modules\User\UserController;
use App\Http\Controllers\Modules\User\UserRoleController;
use App\Http\Controllers\Modules\SettingsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ── Public: landing page ───────────────────────────────────────
Route::get('/', [LandingController::class, 'home'])->name('home');

// ── Public: interactive map ────────────────────────────────────
Route::get('/map', fn() => inertia('Landing/Map', [
    'canLogin' => Route::has('login'),
]))->name('map');

// ── Public: blog detail ────────────────────────────────────────
Route::get('/blog/{slug}', [LandingController::class, 'blogShow'])->name('blog.show');

// ── Public: maintenance notice ─────────────────────────────────
Route::get('/maintenance-notice', MaintenanceNoticeController::class)->name('maintenance-notice');

// ── Super admin console login (bypasses maintenance mode) ──────
// {password} is a shared secret that gates the login form itself — it is
// not a login credential. The super admin still authenticates with their
// real account on the form this reveals. See SuperAdminLoginController.
Route::middleware('throttle:5,1')->where(['password' => '[^/]+'])->group(function () {
    Route::get('/admin/login/{password}', [SuperAdminLoginController::class, 'show'])
        ->name('admin.breakglass-login');
    Route::post('/admin/login/{password}', [SuperAdminLoginController::class, 'store']);
});


Route::middleware(['auth', 'activation'])->group(function () {

    // ── All authenticated users ────────────────────────────────
    Route::get('/dashboard',       [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings',        [SettingsController::class, 'index'])->name('settings');

    // A user holding 2+ roles picks which one is active here; see RoleSwitchController.
    Route::get('/choose-role',   [RoleSwitchController::class, 'edit'])->name('role.choose');
    Route::patch('/switch-role', [RoleSwitchController::class, 'update'])->name('role.switch');

    // Header bell: notifications sent with NotificationHelper::notify()
    Route::get('/notifications',               [NotificationController::class, 'index']);
    Route::post('/notifications/read-all',     [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{id}/read',    [NotificationController::class, 'markRead']);

    // Browser push subscription (KPI reminders)
    Route::post('/push-subscriptions',   [PushSubscriptionController::class, 'store']);
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy']);

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
        Route::post('/users/{id}/roles',              [UserRoleController::class, 'store']);
        Route::patch('/users/{id}/roles/{roleId}',    [UserRoleController::class, 'update']);
        Route::delete('/users/{id}/roles/{roleId}',   [UserRoleController::class, 'destroy']);

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
        Route::post('/maintenance/breakglass-password',               [MaintenanceController::class, 'update_breakglass_password']);
        Route::get('/maintenance/logs',                               [MaintenanceController::class, 'view_logs']);
        Route::get('/maintenance/logs/download',                      [MaintenanceController::class, 'download_logs']);
        Route::post('/maintenance/logs/clear',                        [MaintenanceController::class, 'clear_logs']);

        Route::get('/super-admin-report',        [SuperAdminReportController::class, 'index']);
        Route::get('/super-admin-report/export', [SuperAdminReportController::class, 'export']);

        Route::get('/admins', [UserController::class, 'admin_index']);

    });

    // ── KPI entry: provincial admin enters, provincial director approves ──
    // Quarterly accomplishments + annual targets for their own province. Locks,
    // roll-ups and approvals: see KpiEntryService.
    Route::middleware('role:provincial_admin,provincial_director')->group(function () {
        Route::get('/provincial-kpi/{year?}', [KpiEntryController::class, 'own'])->whereNumber('year');
    });
    Route::middleware('role:provincial_admin')->group(function () {
        Route::put('/provincial-kpi/{year}', [KpiEntryController::class, 'saveOwn'])->whereNumber('year');
    });
    Route::middleware('role:provincial_director')->group(function () {
        Route::post('/provincial-kpi/{year}/quarters/{quarter}/approve', [KpiEntryController::class, 'approve'])
            ->whereNumber(['year', 'quarter']);
    });

    // ── KPI Data Editor + Catalog: Super Admin + Sub Admin only ────
    Route::middleware('role:super_admin,sub_admin')->group(function () {
        // Any province's figures, on the same quarterly entry screen
        Route::get('/kpi-data',              [KPIDataController::class, 'index']);
        Route::get('/kpi-data/{id}/{year?}', [KpiEntryController::class, 'province'])->whereNumber('year');
        Route::put('/kpi-data/{id}/{year}',  [KpiEntryController::class, 'saveProvince'])->whereNumber('year');
        Route::put('/kpi-deadlines/{year}',  [KpiDeadlineController::class, 'update'])->whereNumber('year');

        // KPI catalog — managed as one shared matrix, not per province
        Route::get('/kpi-catalog',                    [KPICatalogController::class, 'index']);
        Route::post('/kpi-catalog/kpis',               [KPICatalogController::class, 'store']);
        Route::put('/kpi-catalog/kpis',                [KPICatalogController::class, 'bulkUpdate']);
        Route::delete('/kpi-catalog/kpis/{kpi}',       [KPICatalogController::class, 'destroy']);
        Route::post('/kpi-catalog/kpis/verify-csv',    [KPICatalogController::class, 'verifyCsv']);
        Route::post('/kpi-catalog/kpis/commit-csv',    [KPICatalogController::class, 'commitCsv']);

        // Catalog save files (restorable versions of the matrix)
        Route::post('/kpi-catalog/snapshots',                    [KpiCatalogSnapshotController::class, 'store']);
        Route::post('/kpi-catalog/snapshots/{snapshot}/restore', [KpiCatalogSnapshotController::class, 'restore']);
        Route::delete('/kpi-catalog/snapshots/{snapshot}',       [KpiCatalogSnapshotController::class, 'destroy']);
    });

    // ── Activity Logs: Super Admin + Provincial Admin + Regional Director ────
    Route::middleware('role:super_admin,provincial_admin,regional_director')->group(function () {
        Route::get('/activity-logs',        [ActivityLogController::class, 'index']);
        Route::get('/activity-logs/report', [ActivityLogController::class, 'generate_logs_report']);
    });

    // Read-only user list also available to Regional Director (scoped to their
    // own region); management actions below stay restricted to Super
    // Admin/Provincial Admin. Sub Admin has no user list access.
    Route::middleware('role:super_admin,provincial_admin,regional_director')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
    });

    // ── Super Admin + Provincial Admin ─────────────────────────
    Route::middleware('role:super_admin,provincial_admin')->group(function () {
        Route::delete('/users/{id}',                                   [UserController::class, 'destroy']);
        Route::patch('/users/{id}/toggle-activation',                  [UserController::class, 'toggle_activation']);

        Route::get('/users/create',                                    [UserController::class, 'manual_registration_index']);
        Route::post('/users/store',                                    [UserController::class, 'store']);
        Route::get('/users/auto-generator',                            [UserController::class, 'auto_registration_index']);
        Route::get('/users/auto-generator/template.xlsx',              [UserController::class, 'generator_template']);
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

    // ── Blog posts management: Super Admin + Sub Admin ─────────
    Route::middleware('role:super_admin,sub_admin')->group(function () {
        Route::get('/blogs',                [BlogController::class, 'index']);
        Route::get('/blogs/create',         [BlogController::class, 'create']);
        Route::post('/blogs',               [BlogController::class, 'store']);
        Route::get('/blogs/{id}/edit',      [BlogController::class, 'edit']);
        Route::post('/blogs/{id}',          [BlogController::class, 'update']);
        Route::delete('/blogs/{id}',        [BlogController::class, 'destroy']);
    });
    
    Route::middleware('role:super_admin,sub_admin')->group(function () {
        Route::get('/performance-map', [DashboardController::class, 'map_index'])->name('performance-map');
    });

    // ── Regions list: Super Admin + Sub Admin only ────────────
    Route::middleware('role:super_admin,sub_admin')->group(function () {
        Route::get('/regions', [RegionController::class, 'index']);
    });

    // ── Regional Director (region overview / map / report) ──
    Route::middleware('role:regional_director')->group(function () {
        Route::get('/regional-performance-map', [DashboardController::class, 'regional_map_index'])->name('regional-performance-map');
        Route::get('/regional-director-report',        [RegionalDirectorReportController::class, 'index']);
        Route::get('/regional-director-report/export', [RegionalDirectorReportController::class, 'export']);
    });


    // ── Provincial Admin only ─────────────────────────────────
    Route::middleware('role:provincial_admin')->group(function () {
        Route::get('/provincial-admin-report',        [ProvincialAdminReportController::class, 'index']);
        Route::get('/provincial-admin-report/export', [ProvincialAdminReportController::class, 'export']);
    });

    // ── Provincial Admin only ──────────────────────────────────
    // KPI matrix editing was removed from here — central admins (super_admin,
    // sub_admin) are now the only roles that can manage KPI data.
    Route::middleware('role:provincial_admin')->group(function () {
        // Public landing page info for their own province — description + cover image
        Route::get('/province-info',  [ProvinceController::class, 'edit_own_info']);
        Route::put('/province-info',  [ProvinceController::class, 'update_own_info']);
    });

    // ── Evidence-based KPI modules (Super Admin + Provincial Admin) ──
    // Each module backs a specific KPI. The accomplishment field for that KPI
    // is locked and derived from the count of records here.
    Route::middleware('role:super_admin,provincial_admin')->group(function () {
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

    // ── Provincial Admin ────────────────────────────────────────
    Route::middleware('role:provincial_admin')->group(function () {
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
