<?php

namespace App\Jobs;

use App\Helpers\NotificationHelper;
use App\Models\User;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

// Shared sending for the KPI reminder jobs: one job = one user's reminder — in
// the system (bell + Reverb) and as a browser push, via NotificationHelper::notify().
// The reminder commands decide who gets what and dispatch the named jobs that
// extend this as one batch (see Bus::batch in the commands), so one user's
// failure doesn't hold up the rest and the batch shows overall progress.
abstract class SendKpiReminder implements ShouldQueue
{
    use Batchable, Queueable;

    // No automatic retry: notify() stores the bell notification before pushing,
    // so a retry could show the same reminder twice. Failed pushes are logged.
    public int $tries = 1;

    public function __construct(
        public int $userId,
        public string $title,
        public string $body,
        public string $url,
        public string $tag,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) return;

        // An error sending this one reminder is logged and skipped, so it
        // never fails the job or holds up the rest of the batch.
        try {
            // Re-checked at send time: an account deactivated since dispatch is skipped.
            $user = User::where('activate', true)->find($this->userId);
            if (!$user) return;

            NotificationHelper::notify($user, $this->title, $this->body, $this->url, 'kpi', $this->tag);
        } catch (Throwable $e) {
            Log::warning("KPI reminder to user {$this->userId} skipped: " . $e->getMessage());
        }
    }
}
