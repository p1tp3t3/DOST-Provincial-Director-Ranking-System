<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /** Paths that are always accessible, even during maintenance. */
    private const ALWAYS_ALLOW = [
        'maintenance-notice',
        'admin/login/*',
        'logout',
        'up',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!Cache::get('app_maintenance_mode', false)) {
            return $next($request);
        }

        // Always-allowed paths
        foreach (self::ALWAYS_ALLOW as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        // Super admins pass through
        if (auth()->check() && auth()->user()->role === 'super_admin') {
            return $next($request);
        }

        // Log out anyone else who happens to be authenticated
        if (auth()->check()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // Remember the page they were after, so the notice can send them back
        // there once maintenance ends (see resources/js/echo.js). Encrypted, so
        // the URL doesn't expose it and it can't be tampered with; the notice
        // route decrypts it.
        $return = $request->isMethod('GET') ? $request->getRequestUri() : null;

        return redirect('/maintenance-notice' . ($return ? '?return=' . urlencode(Crypt::encryptString($return)) : ''));
    }
}
