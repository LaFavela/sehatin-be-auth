<?php

namespace App\Http\Middleware;

use App\Http\Resources\MessageResource;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return (new MessageResource(null, false, 'Token is invalid', $e->getMessage()))->response()->setStatusCode(401);
        }

        return $next($request);
    }
}
