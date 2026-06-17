<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookPost extends Model
{
    protected $fillable = [
        'provincial_director_id',
        'year',
        'title',
        'post_url',
        'post_type',
        'date_posted',
        'caption',
    ];

    protected $casts = [
        'date_posted' => 'date',
        'year'        => 'integer',
    ];

    public function director()
    {
        return $this->belongsTo(User::class, 'provincial_director_id');
    }
}
