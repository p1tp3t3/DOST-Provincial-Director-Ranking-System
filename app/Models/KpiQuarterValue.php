<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One quarter's accomplishment for a province and KPI.
class KpiQuarterValue extends Model
{
    protected $fillable = ['province_id', 'kpi_id', 'year', 'quarter', 'accomplished', 'provincial_director_id', 'updated_by'];

    protected $casts = [
        'year'         => 'integer',
        'quarter'      => 'integer',
        'accomplished' => 'float',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function kpi()
    {
        return $this->belongsTo(KPI::class, 'kpi_id');
    }

    // Who last changed this figure.
    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
