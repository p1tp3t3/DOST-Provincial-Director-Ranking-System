<?php

namespace App\Services;

use App\Models\KpiQuarterValue;
use App\Models\User;
use Illuminate\Support\Collection;

// Who gets KPI reporting reminders, and how much each province still has to
// enter. Used by the deadline and missing-accomplishment reminder commands.
class KpiReminderService
{
    // Reminders go out on Philippine dates, whatever the app timezone is.
    public const TIMEZONE = 'Asia/Manila';

    public function __construct(private KpiEntryService $entries) {}

    // Active users holding $role (as a locked role), with their provinces.
    public function usersWithRole(string $role): Collection
    {
        return User::whereRole($role)
            ->where('activate', true)
            ->with('provinces:id,name,region_id')
            ->get();
    }

    // province_id => [admins and directors] for every province that has one.
    public function provinceRecipients(): Collection
    {
        $byProvince = collect();
        foreach (['provincial_admin', 'provincial_director'] as $role) {
            foreach ($this->usersWithRole($role) as $user) {
                foreach ($user->provinces as $province) {
                    $byProvince->put($province->id, $byProvince->get($province->id, collect())->push($user));
                }
            }
        }
        return $byProvince;
    }

    // How many hand-entered KPIs need a figure for a quarter (same count the
    // province overview uses).
    public function expectedCount(): int
    {
        return $this->entries->entryKpis()->count();
    }

    // province_id => number of hand-entered KPIs still blank for the quarter.
    // Provinces with nothing entered yet are missing everything.
    public function missingByProvince(Collection $provinceIds, int $year, int $quarter): Collection
    {
        $entryIds = $this->entries->entryKpis()->keys();
        $expected = $entryIds->count();

        $entered = KpiQuarterValue::where('year', $year)->where('quarter', $quarter)
            ->whereNotNull('accomplished')
            ->whereIn('kpi_id', $entryIds)
            ->whereIn('province_id', $provinceIds)
            ->selectRaw('province_id, COUNT(DISTINCT kpi_id) as entered')
            ->groupBy('province_id')
            ->pluck('entered', 'province_id');

        return $provinceIds->mapWithKeys(fn($id) => [$id => max(0, $expected - (int) ($entered[$id] ?? 0))]);
    }

    public function isQuarterOpen(int $provinceId, int $year, int $quarter): bool
    {
        return $this->entries->isQuarterOpen($provinceId, $year, $quarter);
    }

    public static function kpis(int $n): string
    {
        return $n === 1 ? '1 KPI' : "{$n} KPIs";
    }
}
