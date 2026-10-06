<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name'];

    // The 5 admin-tier roles — everything except 'employee'. A user may hold
    // at most one of these plus optionally 'employee'; see User::assignRole().
    public const ADMIN_ROLES = [
        'super_admin', 'sub_admin', 'regional_director',
        'provincial_admin', 'provincial_director',
    ];

    public function users() {
        return $this->belongsToMany(User::class, 'user_roles')
                     ->using(UserRole::class)
                     ->withPivot(['locked'])
                     ->withTimestamps();
    }
}
