<?php

namespace App\Http\Middleware\User;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProvincialSubAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()?->role !== 'provincial_sub_admin') {
            return $request->header('X-Inertia')
                ? redirect('/dashboard')
                : abort(403);
        }

        return $next($request);
    }
}
