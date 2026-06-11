<?php

namespace App\Http\Middleware\User;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegionalAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()?->role !== 'regional_admin') {
            return $request->header('X-Inertia')
                ? redirect('/dashboard')
                : abort(403);
        }
        return $next($request);
    }
}
