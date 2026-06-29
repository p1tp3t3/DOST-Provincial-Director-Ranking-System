<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserProvince extends Pivot
{
    public $table = 'user_province';
    public $timestamps = false;
    protected $fillable = ['user_id', 'province_id'];
}
