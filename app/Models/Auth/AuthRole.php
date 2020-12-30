<?php

namespace App\Models\Auth;

use App\Models\Admin\AdminNav;
use App\Models\Common\CommonUser;
use App\Scopes\Auth\AuthRoleScope;
use DB;

use Larfree\Models\Api;

class AuthRole extends Api {
    use AuthRoleScope;
    protected $casts = [
        'tree_nav' => 'array',
    ];

    /**
     * 返回角色对应的菜单
     * @param $name
     * @return mixed
     */
    public function getRoleTreeNav($name) {
        $data = self::query()->where('name', $name)->first();
        if ($data) {
            return $data->tree_nave;
        }
    }

    /**
     * 返回当前用户的菜单
     * @return AdminNav|array
     * @throws \Exception
     */
    public function getUserTreeNav() {

        $data = $this->getUserRole('tree_nav');  //进来用户相应菜单的方法
        $res = [];

        //[0] => [0] => 1 [1] => 2 [2] => 3 [3] => 4 [4] => 5 [5] => 6 [6] => 7 [7] => 8 [8] => 9 [9] => 10 [10] => 11 )
        //[1] => [0] => 1 [1] => 2 [2] => 3 [3] => 5 [4] => 6 [5] => 7 [6] => 8 [7] => 9 [8] => 10 [9] => 11 ) )
        for ($i = 0; $i < count($data); $i++) {
            $res = array_unique(array_merge($data[0], $data[$i]));  //合并并去除掉重复数据
        }
        //[0] => 1 [1] => 2 [2] => 3 [3] => 4 [4] => 5 [5] => 6 [6] => 7 [7] => 8 [8] => 9 [9] => 10 [10] => 11 )  //取并集
        $nav = new AdminNav();
        $nav = $nav->query()->where('status', 1)
            ->where(function ($query) use ($res) {
                if (count($res) != 0) {
                    $query->whereIn('id', $res);
                }
            })
            ->orderBy('ranking', 'desc')->get();  //查询id 对应的菜单
        $nav = $nav->toArray();
        $nav = listToTree($nav, 'id', 'parent_id', 'child');  //就是简单的递归算法  无限极分类


        return $nav;
    }

    /**
     * 获取当前用户的角色相关
     * @param string $colum 默认获取名字字段
     * @return mixed
     * @throws \Exception
     */
    public function getUserRole($colum = 'name') {

        $commonUser = new CommonUser();  //orm 设计模式   代表common_user 这个表
        $data = [];
        // \Illuminate\Support\Facades\DB::table('common_user')->select('id')->find(getLoginUserID()); //不支持link 方法
        //select id from common_user as c  join  link..... as j where c.common_user_id=j.auth_role.id   where id=1
        //link 方法就是查询关联方法
        $info = $commonUser->select('id')->link()->find(getLoginUserID()); //找到当前登录用户  getLoginUserID 获取当前用户的id
        if (@$info->roles) {
            foreach ($info->roles as $k => $item) {
                $data[$k] = ($item->$colum);
            }
        }
        return $data;
    }

    /**
     * 检测用户是否有菜单的打开权限
     * @param $permission_name
     * @param null $group
     * @return bool
     */
    public function checkPermission($permission_name, $group = null) {
        if (empty($this->permission)) {
            return false;
        }
        if ((empty($permission_name) && empty($group)) || in_array('*', $this->permission)) {
            return true;
        }

        foreach ($this->permissions() as $permission) {
            if ($permission->name == $permission_name || $permission->group == $group) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取当前登录用户 admin 对应下的role下对应的scopeId
     * @return array|bool
     * @throws \Exception
     */
    public function getCurrentUserScopeId($cityScope = false) {
        $roles = $this->getUserRole('id'); //返回当前登录角色的roleIdd
        $data = self::query()->link()->select('id')->whereIn('id', $roles)->get();
        if ($data->isEmpty()) {
            return true;
        }
        $res = [];

        foreach ($data as $i => $item) {

            if (!($item->scopes->isEmpty())) {
                foreach ($item->scopes as $k => $src) {

                    if ($src->scope == 'authCity' && $cityScope == true) {
                        $res[] = $src->rule;
                    } elseIf ($cityScope == false && $src->scope != 'authCity') {   //城市scope 得单独考虑
                        $res[] = $src->id;
                    }
                    {

                    }
                }
            }
        }
        $scopeId = (array_unique($res));
        if (@$scopeId) {
            return $scopeId;
        } else {
            return true;
        }


    }


}
