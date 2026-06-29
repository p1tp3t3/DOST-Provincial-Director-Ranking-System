<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiEditUsage extends Model
{
    protected $table = 'kpi_edit_usage';

    protected $fillable = [
        'provincial_director_id',
        'year',
        'used_by',
        'kpi_edit_request_id',
    ];

    public function director()
    {
        return $this->belongsTo(User::class, 'provincial_director_id');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function request()
    {
        return $this->belongsTo(KpiEditRequest::class, 'kpi_edit_request_id');
    }
}
