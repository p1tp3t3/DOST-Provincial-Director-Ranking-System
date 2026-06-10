<?php

namespace App\Http\Middleware\User;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;

class ActivationStatusMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if(auth()->check() && !auth()->user()->activate) {
            auth()->logout();
            return Inertia::location(route('login'))->with('error', 'Your account is deactivated. Please contact your administrator.');
        }
        return $next($request);
    }
}
