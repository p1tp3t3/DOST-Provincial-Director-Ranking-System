<?php

namespace App\Http\Middleware\User;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ProfileViewMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $authUser = Auth::user();

        // Routes without an {id} parameter (e.g. /profile, /profile/picture,
        // /profile-picture) operate on the authenticated user's own profile.
        if (!$request->route()->hasParameter('id')) {
            return $next($request);
        }

        $targetId = (int) $request->route('id');

        // Always allow viewing your own profile
        if ($authUser->id === $targetId) {
            return $next($request);
        }

        // Super admin & sub admin can view any profile
        if (in_array($authUser->role, ['super_admin', 'sub_admin'])) {
            return $next($request);
        }

        $targetUser = User::find($targetId);

        if (!$targetUser) {
            abort(404);
        }

        // Provincial admin, director → anyone in the same province
        if (in_array($authUser->role, ['provincial_admin', 'provincial_director'])) {
            if ($targetUser->province_id === $authUser->province_id) {
                return $next($request);
            }

            abort(403, 'You can only view profiles within your assigned province.');
        }

        // Employees → only the provincial director of their province
        if ($authUser->role === 'employee') {
            if (
                $targetUser->role === 'provincial_director' &&
                $targetUser->province_id === $authUser->province_id
            ) {
                return $next($request);
            }

            abort(403, 'You can only view the provincial director of your province.');
        }

        abort(403);
    }
}
