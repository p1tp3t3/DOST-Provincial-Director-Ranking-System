<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeProfile extends Model
{
    protected $table = 'employee_profiles',
              $fillable = ['profile_id', 'position', 'status'];

    public function profile() {
        return $this->belongsTo(Profile::class, 'profile_id', 'id');
    }
}
