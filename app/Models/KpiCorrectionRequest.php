<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// A request to change a locked quarter (or a year's targets, when quarter is
// null). Provincial admin → regional director → super admin; see
// KpiCorrectionService for the steps.
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
        'applied_at', 'revised_at', 'history',
    ];

    protected $casts = [
        'year'                 => 'integer',
        'quarter'              => 'integer',
        'regional_reviewed_at' => 'datetime',
        'central_reviewed_at'  => 'datetime',
        'open_until'           => 'datetime',
        'applied_at'           => 'datetime',
        'revised_at'           => 'datetime',
        'history'              => 'array',
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

    // Requests still in play for their period: waiting on someone, or approved
    // with the window still open. Only one of these per period at a time.
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn($q) => $q
            ->whereIn('status', [self::PENDING_REGIONAL, self::PENDING_CENTRAL, self::RETURNED])
            ->orWhere(fn($q) => $q->where('status', self::APPROVED)->where('open_until', '>', now())));
    }

    // One period: a quarter, or the year's targets when $quarter is null.
    public function scopeForPeriod(Builder $query, int $provinceId, int $year, ?int $quarter): Builder
    {
        return $query->where('province_id', $provinceId)->where('year', $year)
            ->when($quarter === null, fn($q) => $q->whereNull('quarter'), fn($q) => $q->where('quarter', $quarter));
    }

    // Records a step in the request's history (saved with the next save):
    // { step, by, by_id, at, note, ...$extra }.
    public function addStep(string $step, User $by, ?string $note = null, array $extra = []): static
    {
        $this->history = [...($this->history ?? []), [
            'step'  => $step,
            'by'    => \App\Services\ProvinceOverviewService::personName($by),
            'by_id' => $by->id,
            'at'    => now()->toIso8601String(),
            'note'  => $note,
            ...$extra,
        ]];
        return $this;
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

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::PENDING_REGIONAL => 'Waiting for regional director',
            self::PENDING_CENTRAL  => 'Waiting for central office',
            self::RETURNED         => 'Returned to province',
            self::APPROVED         => $this->isOpen() ? 'Approved · open' : 'Approved · closed',
            self::DECLINED         => 'Declined',
            default                => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }
}
