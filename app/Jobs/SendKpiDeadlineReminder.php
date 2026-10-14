<?php

namespace App\Jobs;

// 3 days / 1 day before a quarter's KPI submission deadline: reminds regional
// directors, provincial admins and provincial directors.
// Dispatched by app:check-kpi-edit-submission-deadline-command.
class SendKpiDeadlineReminder extends SendKpiReminder
{
    public function displayName(): string
    {
        return 'Send KPI Deadline Reminder';
    }
}
