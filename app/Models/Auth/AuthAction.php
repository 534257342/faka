<?php
/**
 * 没有任何逻辑的Model类
 * @author blues
 */

namespace App\Models\Auth;

use App\Models\Common\CommonUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Larfree\Models\Api;
use App\Scopes\Auth\AuthActionScope;


class AuthAction extends Api {

    /**
     *  用于构建gates规则
     *  缓存时间为10分钟
     */
    public function buildGateRules() {
        if (config('app.debug')) {
            $value = self::where('status', 1)->get();
        } else {
            $value = Cache::remember('gateRules', 10, function () {
                $gates = self::where('status', 1)->get();
                return $gates;
            });
        }
        return $value;
    }

    /**
     *
     * 返回当前管理员所具有的 资源action
     * @return mixed
     * @throws \Exception
     */

    public function userAction() {
        $authRole = new AuthRole();
        $role = $this->getRole();
        foreach ($role as $k => $item) {
            $data[] = $authRole->link()->find($item->id);
        }
        foreach ($data as $k => $item) {
            foreach ($item->actions as $i => $value) {
                $temp[$value->id] = $value;
            }
        }
        return $temp;
    }

    /**
     * 定义gate权限.
     * 判断当前用户的role id 是否在具备当前action的role 集合中.
     *
     */
    public function defineGates() {
        $rule = $this->buildGateRules();
        $role = $this->getRole();
        foreach ($rule as $k => $item) {
            $str = $item->resource . '.' . $item->action;
            Gate::define($str, function ($user, $post) use ($role) {
                $roleTemp = [];
                foreach ($post->roles as $k => $item) {
                    $roleTemp[$k] = $item->id;
                }
                foreach ($role as $k => $item) {
                    if (in_array($item->id, $roleTemp)) {
                        return true;
                    }
                }
                return false;
            });
        }
    }


    /**
     * 获取当前用户的roles集合
     * @return mixed
     */
    public function getRole() {
        // Auth::loginUsingId(3); //模拟当前角色为3
        $user = CommonUser::link(['admin'])->where('id', getLoginUserID())->first();
        return $user->admin->roles;
    }


}
