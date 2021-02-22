<?php
/**
 * 基础的apiModel类
 * @author xiao
 */

namespace App\Models\Admin;

use Illuminate\Http\Request;
use Larfree\Models\Api;
use App\Scopes\Admin\AdminOperateRecordScope;

class AdminOperateRecord extends Api {
    use AdminOperateRecordScope;

    /**
     * 记录日志
     * @param Request $request
     * @throws \Exception
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public static function recordRequest(Request $request) {
        $action = \Route::current();
        if (!$action) {
            $class = '未知';
        } else {
            $action = $action->getActionName();
            list($class, $method) = explode('@', $action);
        }
        $model = new self();
        $model->url = $request->getUri();
        $model->user_id = getLoginUserID();
        $model->method = implode('|', $request->route()->methods());
        $model->request = json_encode($request->all());
        $model->ip = $request->getClientIp();
        $model->model = $class;
        $model->name = '数据操作';
        $address = app('address_parse')->getGeoIpCity($request->getClientIp());
        $city = '未知';
        if (!empty($address['city'])) {
            $city = $address['city'];
        }
        $model->address = $city;
        $model->save();
    }
}
