<?php

namespace App\Http\Middleware;

use App\Exceptions\UserSign\SignException;
use App\Models\Admin\AdminOperateRecord;
use App\Models\Common\CommonUser;
use Closure;
use Illuminate\Support\Arr;

class HandleAdminOperateRecord {
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next) {

        $loginid = \Auth()->id();
        if (!$loginid) {
            $loginid = @$_ENV['DEF_USER'];
        }
        if (empty($loginid)) {
            throw new SignException('you are not admin');
        }
        $user = (new CommonUser())->query()->where('id', $loginid)->first();
        if (empty($user->is_admin)) {
            throw new SignException('you are not admin');
        }
        /*        $route  = $request->route();

                // 只记录admin的post请求.
                if (in_array(Arr::first($route->methods), ['PUT', 'POST', 'DELETE'])
                    && substr($route->uri, 0, 9) == 'api/admin') {
                    AdminOperateRecord::recordRequest($request);
                }*/
        return $next($request);
    }
}
