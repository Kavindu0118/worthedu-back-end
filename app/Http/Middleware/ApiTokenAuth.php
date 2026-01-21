<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $auth = $request->header('Authorization') ?? $request->bearerToken();
        if (! $auth) {
            Log::warning('ApiTokenAuth: No Authorization header');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Accept formats like "Bearer TOKEN"
        $token = preg_replace('/^Bearer\s+/i', '', $auth);
        $hashed = hash('sha256', $token);

        $user = User::where('api_token', $hashed)->first();

        if (! $user) {
            Log::warning('ApiTokenAuth: No user found for token', [
                'token_prefix' => substr($token, 0, 10) . '...',
                'hashed_prefix' => substr($hashed, 0, 10) . '...'
            ]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        Log::info('ApiTokenAuth: User authenticated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'path' => $request->path()
        ]);

        // Set the authenticated user for the request
        Auth::setUser($user);

        return $next($request);
    }
}
