<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Profile extends Model
{
    public $table = 'profiles',
           $fillable = ['user_id', 'prefix', 'first_name', 'middle_name', 'last_name', 'profile_picture', 'suffix', 'length_of_service', 'education_attainment'],
           $timestamps = false;

    protected $casts = [
        'education_attainment' => 'array',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function employeeProfile() {
        return $this->hasOne(EmployeeProfile::class, 'profile_id', 'id');
    }
}
