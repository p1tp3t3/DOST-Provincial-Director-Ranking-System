<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['role', 'dost_employee_id', 'username', 'email', 'password', 'activate', 'region_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $appends = ['province'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'activate'          => 'boolean',
        ];
    }

    public function profile() {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }

    public function region() {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }

    public function provinces() {
        return $this->belongsToMany(Province::class, 'user_province', 'user_id', 'province_id')
                     ->using(UserProvince::class);
    }

    // Most roles are tied to a single province; this convenience accessor returns
    // the first (and usually only) related province.
    public function getProvinceAttribute() {
        return $this->provinces->first();
    }

    public function getProvinceIdAttribute() {
        return $this->province?->id;
    }

    public function scopeWhereProvince(Builder $query, ?int $provinceId): Builder {
        return $query->whereHas('provinces', fn($q) => $q->where('provinces.id', $provinceId));
    }

    public function scopeWhereProvinceIn(Builder $query, array $provinceIds): Builder {
        return $query->whereHas('provinces', fn($q) => $q->whereIn('provinces.id', $provinceIds));
    }
}
