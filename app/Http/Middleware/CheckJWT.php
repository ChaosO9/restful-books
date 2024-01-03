<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckJWT
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Attempt to authenticate the user using the JWT from the Authorization header
            if ($user = JWTAuth::parseToken()->authenticate()) {
                $request->merge(['id' => $user->id]);
                return $next($request);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token is invalid'], 401);
        }

        return response()->json(['message' => 'User not found'], 404);
    }
}
