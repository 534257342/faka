<?php
/**
 * 没有任何逻辑的Model类
 * @author blues
 */
namespace App\Models\User;
use Larfree\Models\Api;
use App\Scopes\User\UserActionLogScope;
class UserActionLog extends Api
{
    use UserActionLogScope;

    /**
     * @param $title 操作
     * @param $type 类型
     * @param $ip ip地址
     * @param $from 来源
     * @param $after_content 操作后
     * @param $before_content 操作前
     * @param string $user_id 用户id
     * @param string $site 城市
     * 添加日志
     */
    public function addLog($title,$type,$ip,$from,$after_content,$before_content,$user_id='',$site=''){
        $user_id = $user_id?$user_id:getLoginUserID();//用户id

        $site = $site?$site:getCurrentSite();//城市，站点

        $data =[
            'title'=>$title,
            'type'=>$type,
            'ip'=>$ip,
            'from'=>$from,
            'after_content'=>$after_content,
            'before_content'=>$before_content,
            'user_id'=>$user_id,
            'site'=>$site,
        ];
        $log = $this->create($data);
        if($log){
            return $log;
        }else{
            apiError('添加失败');
        }
    }
}