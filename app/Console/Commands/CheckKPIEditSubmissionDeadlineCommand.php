<?php

namespace App\Console\Commands;

use App\Models\KpiReportingDeadline;
use App\Models\Province;
use App\Jobs\SendKpiDeadlineReminder;
use App\Models\User;
use App\Services\KpiReminderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

#[Signature('app:check-kpi-edit-submission-deadline-command {--dry-run : List who would be notified without sending}')]
#[Description('Remind regional directors, provincial admins and provincial directors 3 days and 1 day before a quarter\'s KPI submission deadline')]
class CheckKPIEditSubmissionDeadlineCommand extends Command
{
    // Days before the deadline that a reminder goes out.
    private const DAYS_BEFORE = [3, 1];

    public function handle(KpiReminderService $reminders): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $today  = now(KpiReminderService::TIMEZONE)->startOfDay();
        $sent   = 0;

        foreach (self::DAYS_BEFORE as $days) {
            $due = $today->copy()->addDays($days);

            foreach (KpiReportingDeadline::whereDate('deadline', $due->toDateString())->get() as $deadline) {
                // One run per deadline per reminder day, even if the command runs again.
                $onceKey = "kpi-deadline-reminder:{$deadline->id}:{$deadline->deadline->toDateString()}:{$days}";
                if (!$dryRun && !Cache::add($onceKey, true, now()->addDays(7))) {
                    $this->line("Already sent: Q{$deadline->quarter} {$deadline->year}, {$days} day(s) before.");
                    continue;
                }

                $sent += $this->remind($reminders, $deadline, $days, $dryRun);
            }
        }

        $this->info($dryRun ? "Dry run: {$sent} user(s) would be reminded." : "Queued deadline reminders for {$sent} user(s).");
        return self::SUCCESS;
    }

    private function remind(KpiReminderService $reminders, KpiReportingDeadline $deadline, int $days, bool $dryRun): int
    {
        $period   = "Q{$deadline->quarter} {$deadline->year}";
        $when     = $days === 1 ? 'tomorrow' : "in {$days} days";
        $dueText  = $deadline->deadline->format('M j, Y');
        $title    = "{$period} KPI submission due {$when}";
        $tag      = "kpi-deadline-{$deadline->year}-q{$deadline->quarter}";
        $url      = "/provincial-kpi/{$deadline->year}";

        $byProvince = $reminders->provinceRecipients();
        $missing    = $reminders->missingByProvince($byProvince->keys(), $deadline->year, $deadline->quarter);
        $sent       = 0;

        // Provincial admins and directors: their own province's progress.
        foreach ($byProvince as $provinceId => $users) {
            $blank = $missing[$provinceId] ?? 0;
            $body  = "The deadline for {$period} accomplishments is {$dueText}. "
                . ($blank > 0
                    ? KpiReminderService::kpis($blank) . ' still ' . ($blank === 1 ? 'needs' : 'need') . ' a figure.'
                    : 'All KPIs have a figure — check them before the deadline.');
            $sent += $this->send($users, $title, $body, $url, $tag, $dryRun);
        }

        // Regional directors: how many provinces in their region are incomplete.
        $provinceRegion = Province::whereIn('id', $byProvince->keys())->pluck('region_id', 'id');
        foreach ($reminders->usersWithRole('regional_director') as $director) {
            $regionProvinces = $byProvince->keys()->filter(fn($id) => $provinceRegion[$id] == $director->region_id);
            $incomplete      = $regionProvinces->filter(fn($id) => ($missing[$id] ?? 0) > 0)->count();
            $body = "The deadline for {$period} accomplishments is {$dueText}. "
                . ($incomplete > 0
                    ? "{$incomplete} of {$regionProvinces->count()} provinces in your region still have missing figures."
                    : 'Every province in your region has entered its figures.');
            $sent += $this->send(collect([$director]), $title, $body, '/dashboard', $tag, $dryRun);
        }

        return $sent;
    }

    // Queue the reminder for these users; the push itself runs on the queue worker.
    private function send(Collection $users, string $title, string $body, string $url, string $tag, bool $dryRun): int
    {
        if ($dryRun) {
            foreach ($users as $user) {
                /** @var User $user */
                $this->line(sprintf('  %-28s %-20s %s', $user->username, $user->lockedRoles()->pluck('name')->diff(['employee'])->implode(','), $body));
            }
        } else {
            SendKpiDeadlineReminder::dispatch($users->pluck('id')->all(), $title, $body, $url, $tag);
        }
        return $users->count();
    }
}
