<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Models\User;
use Illuminate\Http\Request;

// The header bell: the signed-in user's notifications (sent with
// NotificationHelper::notify()) and marking them read.
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user          = $request->user();
        $notifications = $user->notifications()->latest()->take(30)->get();

        // Sender names in one query (sender_id null = the system).
        $senderNames = User::with('profile')
            ->whereIn('id', $notifications->pluck('sender_id')->filter()->unique())
            ->get()
            ->mapWithKeys(fn($s) => [$s->id => NotificationHelper::senderName($s)])
            ->all();

        return response()->json([
            'notifications' => $notifications->map(fn($n) => NotificationHelper::forBell($n, $senderNames))->values(),
            'unread'        => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return response()->noContent();
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
