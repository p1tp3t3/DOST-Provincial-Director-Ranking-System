<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    public $table = 'provinces';

    public function user() {
        return $this->hasMany(User::class, 'province_id', 'id');
    }
    public function provincialDirector() {
        return $this->hasOne(User::class, 'province_id', 'id')
                    ->where('role', 'provincial_director');
    }
}
