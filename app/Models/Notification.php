<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One in-app notification (the header bell). `content` holds
// { type, message, url } — url is where clicking it takes the receiver.
class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'read_since',
    ];

    protected $casts = [
        'content'    => 'array',
        'read_since' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
