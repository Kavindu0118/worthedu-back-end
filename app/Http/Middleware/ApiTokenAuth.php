<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $auth = $request->header('Authorization') ?? $request->bearerToken();
        if (! $auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Accept formats like "Bearer TOKEN"
        $token = preg_replace('/^Bearer\s+/i', '', $auth);
        $hashed = hash('sha256', $token);

        $user = User::where('api_token', $hashed)->first();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Set the authenticated user for the request
        Auth::setUser($user);

        return $next($request);
    }
}
