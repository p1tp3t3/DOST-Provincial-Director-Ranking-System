<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Grant access if the authenticated user's role is any one of the given roles.
     * Usage: Route::middleware('role:super_admin,provincial_admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth()->user();

        if (!$user || !in_array($user->role, $roles)) {
            if ($request->header('X-Inertia')) {
                return redirect('/dashboard');
            }

            abort(403);
        }

        return $next($request);
    }
}
