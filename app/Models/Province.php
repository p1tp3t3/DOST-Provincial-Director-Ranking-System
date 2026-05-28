<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    public $table    = 'provinces';
    protected $fillable = ['name', 'category', 'num_plantilla_employees', 'num_municipalities', 'num_cities'];

    public function user() {
        return $this->hasMany(User::class, 'province_id', 'id');
    }
    public function provincialDirector() {
        return $this->hasOne(User::class, 'province_id', 'id')
                    ->where('role', 'provincial_director');
    }
}
