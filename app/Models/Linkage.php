<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Linkage extends Model
{
    protected $fillable = [
        'provincial_director_id',
        'year',
        'partner_organization',
        'title',
        'type',
        'date_signed',
        'signatories',
        'remarks',
    ];

    protected $casts = [
        'date_signed' => 'date',
        'year'        => 'integer',
    ];

    public function director()
    {
        return $this->belongsTo(User::class, 'provincial_director_id');
    }
}
