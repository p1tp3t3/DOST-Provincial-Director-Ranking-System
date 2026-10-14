<?php

namespace App\Jobs;

use App\Helpers\NotificationHelper;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

// Shared sending for the KPI reminder jobs: one reminder to a group of users,
// e.g. a province's admin and director — in the system (bell + Reverb) and as
// a browser push, via NotificationHelper::notify(). The reminder commands
// decide who gets what and dispatch the named jobs that extend this.
abstract class SendKpiReminder implements ShouldQueue
{
    use Queueable;

    // No automatic retry: notify() stores the bell notification before pushing,
    // so a retry could show the same reminder twice. Failed pushes are logged.
    public int $tries = 1;

    /**
     * @param int[] $userIds
     */
    public function __construct(
        public array $userIds,
        public string $title,
        public string $body,
        public string $url,
        public string $tag,
    ) {}

    public function handle(): void
    {
        // Re-checked at send time: an account deactivated since dispatch is skipped.
        $users = User::whereIn('id', $this->userIds)->where('activate', true)->get();
        if ($users->isEmpty()) return;

        NotificationHelper::notify($users, $this->title, $this->body, $this->url, 'kpi', $this->tag);
    }
}
