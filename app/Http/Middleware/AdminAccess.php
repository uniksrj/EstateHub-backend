<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $user = $request->user();
        if (!in_array($user->role_id, [1,2])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        } 
        
        return $next($request);
    }
}
