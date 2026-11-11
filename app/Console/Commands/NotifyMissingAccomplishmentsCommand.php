<?php

namespace App\Console\Commands;

use App\Models\KpiReportingDeadline;
use App\Jobs\SendMissingAccomplishmentsReminder;
use App\Services\KpiReminderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('app:notify-missing-accomplishments {--dry-run : List who would be notified without sending}')]
#[Description('Weekly reminder to provincial admins and provincial directors about quarterly accomplishments that are still blank')]
class NotifyMissingAccomplishmentsCommand extends Command
{
    public function handle(KpiReminderService $reminders): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $now    = now(KpiReminderService::TIMEZONE);

        // Once per week, even if the command runs again.
        $onceKey = 'missing-accomplishments-reminder:' . $now->format('o-\WW');
        if (!$dryRun && !Cache::add($onceKey, true, now()->addDays(8))) {
            $this->line('Already sent this week.');
            return self::SUCCESS;
        }

        $periods    = $this->periods($now);
        $byProvince = $reminders->provinceRecipients();
        $provinces  = $byProvince->keys();

        // [year-quarter => [province_id => blank count]]
        $missing = [];
        foreach ($periods as [$year, $quarter]) {
            $missing["{$year}-{$quarter}"] = $reminders->missingByProvince($provinces, $year, $quarter);
        }

        $jobs = [];
        foreach ($byProvince as $provinceId => $users) {
            $gaps = [];
            $year = null;
            foreach ($periods as [$y, $q]) {
                $blank = $missing["{$y}-{$q}"][$provinceId] ?? 0;
                if ($blank > 0 && $reminders->isQuarterOpen($provinceId, $y, $q)) {
                    $gaps[] = "Q{$q} {$y}: " . KpiReminderService::kpis($blank);
                    $year ??= $y;
                }
            }
            if (!$gaps) continue;

            $body = 'Still blank — ' . implode('; ', $gaps) . '. Enter them before the deadline.';

            // One job per user.
            foreach ($users as $user) {
                if ($dryRun) $this->line(sprintf('  %-28s %s', $user->dost_employee_id, $body));
                $jobs[] = new SendMissingAccomplishmentsReminder(
                    $user->id,
                    'Missing KPI accomplishments',
                    $body,
                    "/provincial-kpi/{$year}",
                    'kpi-missing-accomplishments',
                );
            }
        }

        // All of this week's reminders go out together as one batch.
        if (!$dryRun && $jobs) {
            $batchName = 'Missing accomplishments reminders — week ' . $now->format('o-\WW');
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

        $count = count($jobs);
        $this->info($dryRun ? "Dry run: {$count} user(s) would be reminded." : "Queued {$count} missing-accomplishment reminder job(s).");
        return self::SUCCESS;
    }

    // [[year, quarter], …] worth checking: this year's quarters that have ended,
    // the current quarter once a deadline is set for it, and last year's
    // quarters whose deadline hasn't passed yet (e.g. a Q4 due in January).
    private function periods($now): array
    {
        $year    = (int) $now->year;
        $current = (int) ceil($now->month / 3);
        $periods = [];

        foreach (KpiReportingDeadline::where('year', $year - 1)->get() as $d) {
            if (!$d->hasPassed()) $periods[] = [$year - 1, $d->quarter];
        }

        $hasDeadline = KpiReportingDeadline::where('year', $year)->pluck('quarter')->flip();
        for ($q = 1; $q <= $current; $q++) {
            if ($q < $current || $hasDeadline->has($q)) $periods[] = [$year, $q];
        }

        return $periods;
    }
}
