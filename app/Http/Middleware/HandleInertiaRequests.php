<?php

namespace App\Http\Middleware;

use App\Services\KpiCorrectionService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->load(['provinces', 'region', 'profile', 'roles', 'activeRole']),
            ],
            // Public key the browser needs to subscribe to push notifications.
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            // Correction requests waiting on this user (sidebar badge).
            'correction_inbox' => fn() => $request->user() ? app(KpiCorrectionService::class)->inboxCount($request->user()) : 0,
        ];
    }
}
