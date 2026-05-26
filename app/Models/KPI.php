<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KPI extends Model
{
    public $table = 'kpis', 
           $fillable = ['id', 'outcome_title', 'sub_rows'];
}
