<?php

namespace App\Jobs;

// Weekly: reminds a province's provincial admin and provincial director about
// quarterly accomplishments that are still blank.
// Dispatched by app:notify-missing-accomplishments.
class SendMissingAccomplishmentsReminder extends SendKpiReminder
{
    public function displayName(): string
    {
        return 'Send Missing Accomplishments Reminder';
    }
}
