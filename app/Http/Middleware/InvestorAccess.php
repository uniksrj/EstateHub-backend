<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvestorAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
     public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (int) $user->role_id !== 7) {
            return response()->json([
                'message' => 'Investor access required.',
            ], 403);
        }

        if (isset($user->is_active) && ! $user->is_active) {
            return response()->json(['message' => 'Account disabled.'], 403);
        }

        return $next($request);
    }
}
