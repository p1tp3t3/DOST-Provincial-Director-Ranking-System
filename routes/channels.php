<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('kpi-catalog', function ($user) {
    return in_array($user->role, ['super_admin', 'sub_admin'], true);
});
