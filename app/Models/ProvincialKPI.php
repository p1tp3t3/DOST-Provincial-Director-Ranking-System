<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProvincialKPI extends Model
{
    public $table      = 'provincial_kpis';
    public $timestamps = false;
    protected $fillable = ['province_id', 'kpi_id', 'target', 'year'];

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function kpi()
    {
        return $this->belongsTo(KPI::class, 'kpi_id');
    }

    // The matching ProvincialDirectorKPI (same kpi_id + year) reported by the
    // province, if any.
    public function getDirectorKpiAttribute(): ?ProvincialDirectorKPI
    {
        return ProvincialDirectorKPI::where('province_id', $this->province_id)
            ->where('kpi_id', $this->kpi_id)
            ->where('year', $this->year)
            ->first();
    }
}
