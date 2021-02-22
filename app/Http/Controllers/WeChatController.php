<?php
/**
 * Created by PhpStorm.
 * User: yons
 * Date: 2018/11/7
 * Time: 下午2:48
 */

namespace App\Http\Controllers;


use app\common\model\Response;
use App\Models\Common\CommonUser;
use App\Models\System\SystemConfig;
use EasyWeChat\Factory;
use EasyWeChatComposer\EasyWeChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use think\Exception;
use Tymon\JWTAuth\Facades\JWTAuth;

class WeChatController extends Controller {
    /**
     * @param Request $request
     * 获取微信参数
     */
    public function getParam(Request $request) {
        $user = session('wechat.oauth_user'); // 拿到授权用户资料
        $openid = $user['default']['original']['openid'];
        $unionid = $user['default']['original']['unionid'];
        $nickname = $user['default']['original']['nickname'];
        $url = $request->url;
        //执行微信登录
        $url = $url . "/openid/" . $openid . '/unionid/' . $unionid . '/nickname/' . $nickname;
        Log::debug('微信参数获取:' . $url);
        return redirect($url);
    }

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
                        "url"  => "https://wechat.dml-express.com/#/1",
                    ],
                    [
                        "type" => "view",
                        "name" => "上海下单",
                        "url"  => "https://wechat.dml-express.com/#/2",
                    ],
                    [
                        "type" => "view",
                        "name" => "深圳下单",
                        "key"  => "https://wechat.dml-express.com/#/3",
                    ],
                    //                    [
                    //                        "type" => "click",
                    //                        "name" => "广州下单",
                    //                        "key" => "V1001_GOOD"
                    //                    ],
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
    public function getMenu(Request $request) {

        $app = app('wechat.mini_program');
        $res = $app->app_code->getQrCode('index/home');
        return $res;
        // $res = $miniProgram->template_message->getTemplates(0, 10);
        $list = $miniProgram->template_message->list(0, 10);
        exit();
        //$app = \EasyWeChat::miniProgram();
        // $app = Factory::miniProgram($config);
        $app = app('wechat.official_account');
        $list = $officialAccount->menu->list();
        return $list;
    }

    /**
     * @param Request $request
     * @return string
     * 生成微信二维码
     */
    public function weChatQr(Request $request) {
        if (!$request->input('code')) {
            return '请输入邀请码或者手机号,"?code=42cb或者code=15888886666"';
        }
        $code = $request->code;
        $result = preg_match("/^1[3456789]\d{9}$/", $code);
        if ($result) {
            $code = CommonUser::query()
                ->where('phone', $code)
                ->orderBy('id', 'desc')
                ->first()
                ->invite_code;
        }
        $app = app('wechat.official_account');

        $result = $app->qrcode->forever($code);//创建永久二维码
        $url = $app->qrcode->url($result['ticket']);//获取二维码网址

        return "<div style='width: 430px;'><img src='$url'><div style='text-align: center;'>当前邀请码：$code</div></div>";

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
                        if ($message['Event'] == 'CLICK') {//微信点击自定义菜单拉取消息时的事件推送
                            $config = SystemConfig::query()->where('key', 'service')->first();
                            return $config->value[0];//客服消息
                        } else {
                            $notice = $this->notice($EventKey[1]);
                            return $notice;
                        }
                }
            } else {
                $config = SystemConfig::query()->where('key', 'ordinary_notice')->first();
                return $config->value[0];//普通推送消息
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
        $user = CommonUser::query()->where('invite_code', $code)->first();
        if (!$user) {
            return '无此邀请码';
        }
        $config = SystemConfig::query()->where('key', 'invite_notice')->first();
        return str_replace('code', $code, $config->value[0]);
    }

    /**
     * 微信小程序登录
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Larfree\Exceptions\ApiException
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public function weAppLogin(Request $request) {
        $miniProgram = \EasyWeChat::miniProgram();
        if (empty($request->code)) {
            apiError('请输入code');
        }
        $data = $miniProgram->auth->session($request->code);
        if (isset($data['errcode'])) {
            Log::debug(json_encode($data));
            apiError('code不存在或已经过期');
        }
        $weappOpenid = $data['openid'];
        $weixinSessionKey = $data['session_key'];
        $user = CommonUser::where('openid', $weappOpenid)->first();
        $avatar = str_replace('/132', '/0', $request->avatar);
        if (!$user) {
            $id = CommonUser::insertGetId([
                'openid' => $weappOpenid,
                'avatar' => $avatar,
                'name'   => $request->name,
                'phone'  => $request->phone,
                'city'   => $request->city,
                'sex'    => $request->sex ?? 2
            ]);
            $user = CommonUser::query()->where('id', $id)->first();
        }
        $attributes['updated_at'] = now();
        $attributes['password'] = $weixinSessionKey;
        $attributes['avatar'] = $avatar;
        $user->update($attributes);
        $token = JWTAuth::fromUser($user);
        $data = [
            'access_token' => $token,
            'token_type'   => "Bearer",
            'data'         => $user,
        ];
        return Response()->success($data, '登录成功');
    }

    public function getRobot(Request $request) {
        $wechaty = \IO\Github\Wechaty\Wechaty::getInstance($token, $endPoint);
        $wechaty->onScan(function($qrcode, $status, $data) {
            $qr = \IO\Github\Wechaty\Util\QrcodeUtils::getQr($qrcode);
            echo "$qr\n\nOnline Image: https://wechaty.github.io/qrcode/$qrcode\n";
        })->onLogin(function(\IO\Github\Wechaty\User\ContactSelf $user) {
        })->onMessage(function(\IO\Github\Wechaty\User\Message $message) {
            $message->say("hello from PHP7.4");
        })->start();
    }
}
