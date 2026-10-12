<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserRole extends Pivot
{
    public $table = 'user_roles';
    protected $fillable = ['user_id', 'role_id', 'locked'];
    protected $casts = ['locked' => 'boolean'];
}
