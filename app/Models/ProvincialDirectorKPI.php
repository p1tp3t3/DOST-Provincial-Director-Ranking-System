<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A province's annual target and accomplishment for one KPI — the figure the
// ranking uses. Keyed by province; provincial_director_id is the director in
// office when it was entered. When the province reports quarterly, the
// accomplishment here is rolled up from kpi_quarter_values (see KpiEntryService).
class ProvincialDirectorKPI extends Model
{
    public $table = 'provincial_director_kpis';
    protected $fillable = ['province_id', 'provincial_director_id', 'kpi_id', 'target', 'accomplished', 'year', 'updated_by'];

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function director()
    {
        return $this->belongsTo(User::class, 'provincial_director_id');
    }

    public function kpi()
    {
        return $this->belongsTo(KPI::class, 'kpi_id');
    }
}
