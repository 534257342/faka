<?php
/**
 * 没有任何逻辑的Model类
 * @author blues
 */

namespace App\Models\User;

use Larfree\Models\Api;
use App\Scopes\User\UserActionLogScope;

class UserActionLog extends Api {
    use UserActionLogScope;

    /**
     * 新增操作日志
     * @param $title
     * @param $type
     * @param $ip
     * @param $from
     * @param $after_content
     * @param $before_content
     * @param string $user_id
     * @param string $site
     * @return UserActionLog|bool
     * @throws \Larfree\Exceptions\ApiException
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public function addLog($title, $type, $ip, $from, $after_content, $before_content, $user_id = '', $site = '') {
        $user_id = $user_id ? $user_id : getLoginUserID();//用户id

        $site = $site ? $site : getCurrentSite();//城市，站点

        $data = [
            'title'          => $title,
            'type'           => $type,
            'ip'             => $ip,
            'from'           => $from,
            'after_content'  => $after_content,
            'before_content' => $before_content,
            'user_id'        => $user_id,
            'site'           => $site,
        ];
        $log = $this->create($data);
        if ($log) {
            return $log;
        } else {
            apiError('添加失败');
        }
    }
}
