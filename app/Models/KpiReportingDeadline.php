<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Last day provinces can enter a quarter's accomplishments.
class KpiReportingDeadline extends Model
{
    protected $fillable = ['year', 'quarter', 'deadline', 'set_by'];

    protected $casts = [
        'year'     => 'integer',
        'quarter'  => 'integer',
        'deadline' => 'date',
    ];

    // Open through the end of the deadline day.
    public function hasPassed(): bool
    {
        return now()->greaterThan($this->deadline->copy()->endOfDay());
    }
}
