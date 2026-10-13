<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A request to change a locked quarter (or a year's targets, when quarter is
// null). Provincial admin → regional director → super admin.
class KpiCorrectionRequest extends Model
{
    public const PENDING_REGIONAL = 'pending_regional';
    public const PENDING_CENTRAL  = 'pending_central';
    public const APPROVED         = 'approved';
    public const DECLINED         = 'declined';
    public const RETURNED         = 'returned';

    protected $fillable = [
        'province_id', 'year', 'quarter', 'reason', 'requested_by', 'status',
        'regional_reviewer_id', 'regional_reviewed_at', 'regional_note',
        'central_reviewer_id', 'central_reviewed_at', 'central_note', 'open_until',
    ];

    protected $casts = [
        'year'                 => 'integer',
        'quarter'              => 'integer',
        'regional_reviewed_at' => 'datetime',
        'central_reviewed_at'  => 'datetime',
        'open_until'           => 'datetime',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function regionalReviewer()
    {
        return $this->belongsTo(User::class, 'regional_reviewer_id');
    }

    public function centralReviewer()
    {
        return $this->belongsTo(User::class, 'central_reviewer_id');
    }

    // "Q2 2026" or "2026 targets".
    public function periodLabel(): string
    {
        return $this->quarter ? "Q{$this->quarter} {$this->year}" : "{$this->year} targets";
    }

    public function isOpen(): bool
    {
        return $this->status === self::APPROVED && $this->open_until?->isFuture();
    }
}
