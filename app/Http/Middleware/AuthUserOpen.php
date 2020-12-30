<?php

namespace App\Http\Middleware;

use App\Exceptions\UserSign\SignException;
use App\Models\User\UserOpen;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthUserOpen
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
            Log::debug('第三方请求参数URL:'.$request->url(),$request->all());
            if(!$request->input('api_key')){

                throw new SignException('please enter api_key');
            }

            if(!$request->input('sign')){

                throw new SignException('please enter sign');
            }

            if(!$request->input('timestamp')){

                throw new SignException('please enter timestamp');
            }
            $UserOpen = new UserOpen();

            $userOpen = $UserOpen->authUser($request->all());
            
            if($userOpen){

                Auth::loginUsingId($userOpen->user_id);
            }

        }
        return $next($request);
    }
}
