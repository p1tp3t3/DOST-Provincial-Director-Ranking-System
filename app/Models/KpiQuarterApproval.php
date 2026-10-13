<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// The provincial director's sign-off on a quarter's figures.
class KpiQuarterApproval extends Model
{
    protected $fillable = ['province_id', 'year', 'quarter', 'approved_by', 'note'];

    protected $casts = [
        'year'    => 'integer',
        'quarter' => 'integer',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
