<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPICategory extends Model
{
    public $table      = 'kpi_categories';
    public $timestamps = false;
    protected $fillable = ['code', 'name', 'weight', 'sort_order'];

    public function kpis()
    {
        return $this->hasMany(KPI::class, 'category_id')->orderBy('sort_order');
    }
}
