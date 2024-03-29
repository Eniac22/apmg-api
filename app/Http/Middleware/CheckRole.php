<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        try {
            $user = Auth::user();
            if ($user && in_array($user->role, $roles)) {
                return $next($request);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized Access'], 403);
        }

        return response()->json(['error' => 'Unauthorized Access'], 403);
    }
}
