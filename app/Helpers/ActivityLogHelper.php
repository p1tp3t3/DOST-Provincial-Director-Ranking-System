<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ActivityLogHelper
{
    // ── Auth ──────────────────────────────────────────────────────

    public static function login(): void
    {
        self::log('log in', self::actorName() . ' logged into the system.');
    }

    public static function logout(): void
    {
        self::log('log out', self::actorName() . ' logged out of the system.');
    }

    // ── User / Account ────────────────────────────────────────────

    public static function createUser(User $target): void
    {
        self::log('create',
            self::actorName() . ' created a new ' . self::roleLabel($target->role) . ' account for ' . self::name($target) . '.'
        );
    }

    public static function updateUser(User $target): void
    {
        self::log('update',
            self::actorName() . ' updated the profile of ' . self::name($target) . '.'
        );
    }

    public static function deleteUser(User $target): void
    {
        self::log('delete',
            self::actorName() . ' deleted the account of ' . self::name($target) . ' (' . ($target->dost_employee_id ?? $target->username) . ').'
        );
    }

    public static function updatePassword(User $target): void
    {
        $actor  = Auth::user();
        $isSelf = $actor?->id === $target->id;

        self::log('update',
            $isSelf
                ? self::actorName() . ' changed their own password.'
                : self::actorName() . ' changed the password of ' . self::name($target) . '.'
        );
    }

    // ── Province ──────────────────────────────────────────────────

    public static function createProvince(string $provinceName): void
    {
        self::log('create', self::actorName() . " created a new province: {$provinceName}.");
    }

    public static function updateProvince(string $provinceName, string $field = ''): void
    {
        $detail = $field ? " ({$field})" : '';
        self::log('update', self::actorName() . " updated province{$detail}: {$provinceName}.");
    }

    // ── KPI ───────────────────────────────────────────────────────

    public static function updateKpi(User $director, string $outcome = ''): void
    {
        $detail = $outcome ? ' - "' . $outcome . '"' : '';
        self::log('update',
            self::actorName() . ' updated KPI record' . $detail . ' for ' . self::name($director) . '.'
        );
    }

    // ── Account Generation ────────────────────────────────────────

    public static function generateAccounts(int $count, string $provinceName): void
    {
        self::log('generate',
            self::actorName() . " generated {$count} employee account(s) for {$provinceName}."
        );
    }

    // ── Reports & Exports ─────────────────────────────────────────

    public static function exportReport(string $reportName, string $format = 'PDF'): void
    {
        self::log('export', self::actorName() . " exported the {$reportName} report ({$format}).");
    }

    public static function exportActivityLogs(string $dateFrom, string $dateTo, string $format = 'PDF'): void
    {
        self::log('export',
            self::actorName() . " exported activity logs ({$format}) from {$dateFrom} to {$dateTo}."
        );
    }

    // ── Views ─────────────────────────────────────────────────────

    public static function viewPage(string $page): void
    {
        self::log('view', self::actorName() . " viewed the {$page}.");
    }

    // ── Backup / Maintenance ──────────────────────────────────────

    public static function createBackup(string $filename): void
    {
        self::log('create', self::actorName() . " created a database backup: {$filename}.");
    }

    public static function deleteBackup(string $filename): void
    {
        self::log('delete', self::actorName() . " deleted database backup: {$filename}.");
    }

    public static function clearCache(string $cacheType): void
    {
        self::log('update', self::actorName() . " cleared the {$cacheType} cache.");
    }

    public static function resetDatabase(): void
    {
        self::log('delete', self::actorName() . ' reset and re-seeded the database.');
    }

    // ── Core ──────────────────────────────────────────────────────

    private static function log(string $type, string $description): void
    {
        $user = Auth::user();
        if (!$user) return;

        ActivityLog::create([
            'user_id'     => $user->id,
            'type'        => $type,
            'description' => $description,
        ]);
    }

    private static function actorName(): string
    {
        return self::name(Auth::user());
    }

    private static function name(?User $user): string
    {
        if (!$user) return 'Unknown';

        $profile = $user->relationLoaded('profile') ? $user->profile : $user->profile()->first();

        if ($profile && ($profile->first_name || $profile->last_name)) {
            $middle = $profile->middle_name ? " {$profile->middle_name}" : '';
            return trim("{$profile->first_name}{$middle} {$profile->last_name}");
        }

        return $user->username ?? "User #{$user->id}";
    }

    private static function roleLabel(string $role): string
    {
        return match ($role) {
            'super_admin'          => 'Super Admin',
            'sub_admin'            => 'Sub Admin',
            'provincial_admin'     => 'Provincial Admin',
            'provincial_sub_admin' => 'Provincial Sub Admin',
            'provincial_director'  => 'Provincial Director',
            'employee'             => 'Employee',
            default                => ucfirst(str_replace('_', ' ', $role)),
        };
    }
}
