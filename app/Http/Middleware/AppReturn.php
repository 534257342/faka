<?php

namespace App\Http\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Closure;

class AppReturn
{

    /**
     * 为了方便app调用
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $return = $next($request);
        if($request->headers->get('device')=='app' && $return->getStatusCode()<500) {
            $return->setStatusCode(200);

            //app那边需要强制转换成对象
            $json=json_decode($return->getContent(),true);
            if(count($json['data']) == 0){
                $json['data']=null;
                unset($json['data']);
                return new JsonResponse($json);
//            return json_encode($json);
            }
        }






        return $return;
    }
}
