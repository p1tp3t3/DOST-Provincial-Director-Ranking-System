<?php

namespace App\Http\Middleware\User;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmployeeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()?->role !== 'employee') {
            return $request->header('X-Inertia')
                ? redirect('/dashboard')
                : abort(403);
        }

        return $next($request);
    }
}
