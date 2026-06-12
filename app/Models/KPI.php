<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPI extends Model
{
    public $table      = 'kpis';
    public $timestamps = false;
    protected $fillable = [
        'category_id', 'code', 'name', 'weight',
        'is_scored', 'inverse_scoring', 'derivation_type', 'sort_order',
    ];
    protected $casts = [
        'weight'          => 'float',
        'is_scored'       => 'boolean',
        'inverse_scoring' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(KPICategory::class, 'category_id');
    }

    public function directorScores()
    {
        return $this->hasMany(ProvincialDirectorKPI::class, 'kpi_id');
    }

    public function provincialKpis()
    {
        return $this->hasMany(ProvincialKPI::class, 'kpi_id');
    }
}
