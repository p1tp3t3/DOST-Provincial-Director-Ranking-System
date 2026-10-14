<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

// Saves / removes the browser push subscription for the signed-in user
// (one per browser), used to deliver KPI reminders.
class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint'        => ['required', 'url', 'max:500'],
            'keys.p256dh'     => ['required', 'string', 'max:255'],
            'keys.auth'       => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:20'],
        ]);

        $request->user()->updatePushSubscription(
            $data['endpoint'],
            $data['keys']['p256dh'],
            $data['keys']['auth'],
            $data['contentEncoding'] ?? 'aes128gcm',
        );

        return response()->noContent();
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['endpoint' => ['required', 'url', 'max:500']]);

        $request->user()->deletePushSubscription($data['endpoint']);

        return response()->noContent();
    }
}
