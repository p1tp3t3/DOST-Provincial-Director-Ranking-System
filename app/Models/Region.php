<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    public $table = 'region';

    protected $fillable = ['name', 'island_under'];

    public function provinces()
    {
        return $this->hasMany(Province::class, 'region_id', 'id');
    }
}
