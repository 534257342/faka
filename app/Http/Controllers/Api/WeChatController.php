<?php
/**
 * Created by PhpStorm.
 * User: xiao
 * Date: 2018/11/7
 * Time: 下午2:48
 */

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Common\CommonUser;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class WeChatController extends Controller {
    /**
     * 微信自定义菜单
     */
    public function addMenu() {
        $app = app('wechat.official_account');
        $buttons = [
            [
                "name"       => "立即下单",
                "sub_button" => [
                    [
                        "type" => "view",
                        "name" => "北京下单",
                        "url"  => "https://wechat.dml-express.com",
                    ],
                    [
                        "type" => "view",
                        "name" => "上海下单",
                        "url"  => "http://api.dml-express.com/redirect.php",
                    ],
                    [
                        "type" => "view",
                        "name" => "深圳下单",
                        "url"  => "https://wechat.dml-express.com/?1547538396817=#/home",
                    ],
                ],
            ],
            [
                "type" => "click",
                "name" => "联系客服",
                "key"  => "app_download"
            ],
            [
                "type" => "view",
                "name" => "运单查询",
                "url"  => "https://wechat.dml-express.com/?1547710605711#/myexpress/search"
            ],
        ];
        $menu = $app->menu->create($buttons);
        return $menu;

    }

    /**
     * @return mixed
     * 获取自定义菜单
     */
    public function getMenu() {

        $app = app('wechat.official_account');

        $list = $app->menu->list();
        return $list;
    }

    /**
     * @OA\Get(
     *   summary="根据邀请码生成二维码",
     *   description="根据邀请码生成二维码",
     *   path="/wechat/qr?code=42cd",
     *   tags={"财务流水相关"},
     *   security={{
     *     "jwt":{}
     *   }},
     *   @OA\Response(
     *     response="200",
     *     description="请求成功 md5:ba1b8e1ac5568548ca2c2269cd9b2676",
     *   ),
     * )
     */
    public function weChatQr(Request $request) {

        if (!$request->input('code')) {

            return '请输入邀请码,"?code=42cb"';
        }
        $code = $request->code;

        $app = app('wechat.official_account');

        $result = $app->qrcode->forever($code);//创建永久二维码

        $url = $app->qrcode->url($result['ticket']);//获取二维码网址

        return $url;

    }

    /**
     * @param Request $request
     * @return mixed
     * 微信关注公众推送消息
     */
    public function serve(Request $request) {
        $app = app('wechat.official_account');
        $app->server->push(function ($message) {
            if ($message['EventKey']) {
                $EventKey = explode('_', $message['EventKey']);
                switch ($message['MsgType']) {
                    case 'event':
                        //if($message['Event'] == 'CLICK'){//微信点击自定义菜单拉取消息时的事件推送
                        return '欢迎进入，大马鹿(叮咚)，当前邀请码为:，<a href="http://dev-wechat3-damalu.ddchuansong.com/?#/enter/register">点击注册。</a>';
//                        }else{
//                            $notice = $this->notice($EventKey[1]);
//                            return $notice;
//                        }
                }
            }
        });
        return $app->server->serve();
    }

    /**
     * @param $code
     * @return string
     * 根据邀请码获取不同的推送文字
     */
    public function notice($code) {

        $Users = new User();
        $user = $Users->where('invite_code', $code)->first();
        if (!$user) {
            return '无此邀请码';
        }
        if (!$user->notice) {
            $user->notice = '欢迎进入，大马鹿(叮咚)，当前邀请码为:，<a href="https://wechat-damalu.ddchuansong.com/#/register/">点击注册。</a>';
            $user->save();
            $notice = explode(':', $user->notice);
            $nodices = $notice[0] . ':' . $code . $notice[1] . ':';
            $url = explode('"', $notice[2]);
            return $nodices . $url[0] . $code . '"' . $url[1];
        } else {
            if (strpos($user->notice, '当前邀请码为')) {
                $notice = explode(':', $user->notice);
                $nodices = $notice[0] . ':' . $code . $notice[1] . ':';
                $url = explode('"', $notice[2]);
                return $nodices . $url[0] . $code . '"' . $url[1];
            }
            $url = explode('"', $user->notice);

            return $url[0] . '"' . $url[1] . $code . '"' . $url[2];
        }
    }
}
