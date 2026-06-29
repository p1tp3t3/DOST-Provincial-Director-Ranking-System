<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiEditRequest extends Model
{
    protected $fillable = [
        'provincial_director_id',
        'requested_by',
        'year',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'response_note',
        'used_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'used_at'     => 'datetime',
    ];

    public function director()
    {
        return $this->belongsTo(User::class, 'provincial_director_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
