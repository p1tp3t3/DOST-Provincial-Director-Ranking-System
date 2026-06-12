<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    public $table    = 'provinces';
    protected $fillable = ['name', 'category', 'region_id', 'num_plantilla_employees', 'num_municipalities', 'num_cities'];

    public function region() {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }

    public function users() {
        return $this->belongsToMany(User::class, 'user_province', 'province_id', 'user_id')
                     ->using(UserProvince::class);
    }

    public function directorAssignments() {
        return $this->users()->where('role', 'provincial_director');
    }

    public function provincialKpis() {
        return $this->hasMany(ProvincialKPI::class, 'province_id');
    }

    public function getProvincialDirectorAttribute() {
        return $this->directorAssignments->first();
    }
}
