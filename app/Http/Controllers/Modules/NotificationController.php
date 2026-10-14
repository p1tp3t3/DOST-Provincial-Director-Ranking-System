<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index() {
        return inertia("Other/Notification/Main");
    }

    // The header bell marks a notification read when it's clicked.
    public function markRead(Request $request, Notification $notification) {
        abort_unless((int) $notification->receiver_id === (int) $request->user()->id, 403);
        if (!$notification->read_since) {
            $notification->update(['read_since' => now()]);
        }
        return back();
    }

    public function markAllRead(Request $request) {
        Notification::where('receiver_id', $request->user()->id)->whereNull('read_since')
            ->update(['read_since' => now()]);
        return back();
    }
}
