<?php

namespace App\Console\Commands;

use App\Models\KpiReportingDeadline;
use App\Models\Province;
use App\Jobs\SendKpiDeadlineReminder;
use App\Models\User;
use App\Services\KpiReminderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

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

        $this->info($dryRun ? "Dry run: {$sent} user(s) would be reminded." : "Queued {$sent} deadline reminder job(s).");
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
        $jobs       = [];

        // Provincial admins and directors: their own province's progress.
        foreach ($byProvince as $provinceId => $users) {
            $blank = $missing[$provinceId] ?? 0;
            $body  = "The deadline for {$period} accomplishments is {$dueText}. "
                . ($blank > 0
                    ? KpiReminderService::kpis($blank) . ' still ' . ($blank === 1 ? 'needs' : 'need') . ' a figure.'
                    : 'All KPIs have a figure — check them before the deadline.');
            $this->collect($jobs, $users, $title, $body, $url, $tag, $dryRun);
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
            $this->collect($jobs, collect([$director]), $title, $body, '/dashboard', $tag, $dryRun);
        }

        if ($dryRun) return count($jobs);

        // One job per user, sent together as one batch.
        if ($jobs) {
            $batchName = "KPI deadline reminders — {$period} ({$days} " . ($days === 1 ? 'day' : 'days') . ' before)';
            // Sending is a nice-to-have: if queueing the batch fails, log it and
            // carry on (the scheduler tries again on its next run).
            try {
                Bus::batch($jobs)
                    ->name($batchName)
                    ->allowFailures()
                    ->finally(function (Batch $batch) use ($batchName) {
                        Log::info("{$batchName}: " . ($batch->totalJobs - $batch->failedJobs) . "/{$batch->totalJobs} sent.");
                    })
                    ->dispatch();
            } catch (Throwable $e) {
                Log::warning('Could not queue KPI reminders: ' . $e->getMessage());
                $this->warn('Could not queue reminders: ' . $e->getMessage());
            }
        }

        return count($jobs);
    }

    // Add one reminder job per user to the batch (or list them on a dry run).
    private function collect(array &$jobs, Collection $users, string $title, string $body, string $url, string $tag, bool $dryRun): void
    {
        foreach ($users as $user) {
            /** @var User $user */
            if ($dryRun) {
                $this->line(sprintf('  %-28s %-20s %s', $user->dost_employee_id, $user->lockedRoles()->pluck('name')->diff(['employee'])->implode(','), $body));
            }
            $jobs[] = new SendKpiDeadlineReminder($user->id, $title, $body, $url, $tag);
        }
    }
}
