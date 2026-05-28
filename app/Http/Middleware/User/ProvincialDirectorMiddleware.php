<?php

namespace App\Http\Middleware\User;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProvincialDirectorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(auth()->user()->role != 'provincial_director')
            return response()->json([
                        'success' => false,
                        'message' => 'provincial director is authorized to access here'
                    ], 403);
        return $next($request);
    }
}
