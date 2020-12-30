<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthJWT
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!env('DEF_USER')){
            try {
                if (!$user = JWTAuth::parseToken()->authenticate()) {
                    apiError('user not found','',401);
                }
            } catch (TokenExpiredException $e) {
                apiError('token expired','',401);
            } catch (TokenInvalidException $e) {
                apiError('token invalid','',401);
            } catch (JWTException $e) {
                apiError('token absent','',401);
            }
        }
        return $next($request);
    }
}
