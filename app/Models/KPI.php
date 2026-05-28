<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPI extends Model
{
    public $table    = 'kpis';
    protected $fillable = ['id', 'outcome_title'];

    public function subRows()
    {
        return $this->hasMany(KPISubrow::class, 'kpi_id')->orderBy('id');
    }
}
