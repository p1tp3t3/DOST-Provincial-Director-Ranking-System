<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProvincialDirectorKPI extends Model
{
    public $table      = 'provincial_director_kpis';
    public $timestamps = false;
    protected $fillable = ['provincial_director_id', 'kpi_id', 'target', 'accomplished', 'year'];

    public function director()
    {
        return $this->belongsTo(User::class, 'provincial_director_id');
    }

    public function kpi()
    {
        return $this->belongsTo(KPI::class, 'kpi_id');
    }
}
