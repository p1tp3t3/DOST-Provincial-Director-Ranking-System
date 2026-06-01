<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPISubrow extends Model
{
    public $table      = 'kpi_subrows';
    public $timestamps = false;
    protected $fillable = ['kpi_id', 'description'];

    public function kpi()
    {
        return $this->belongsTo(KPI::class, 'kpi_id');
    }

    public function directorScores()
    {
        return $this->hasMany(ProvincialDirectorKPI::class, 'kpi_subrow_id');
    }
}
