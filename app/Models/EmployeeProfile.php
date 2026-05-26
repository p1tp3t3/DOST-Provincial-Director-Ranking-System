<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    protected $table = 'employee_profiles',
              $fillable = [];

    public function profile() {
        return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }
}
