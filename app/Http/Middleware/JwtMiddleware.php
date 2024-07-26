<?php

namespace App\Http\Middleware;
use JWTAuth;
use Closure;
use Illuminate\Http\Request;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ($guard != null) {
            auth()->shouldUse($guard);
            JWTAuth::parseToken()->authenticate();
        }
        return $next($request);
    }
}
