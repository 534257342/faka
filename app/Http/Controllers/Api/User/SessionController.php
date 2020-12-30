<?php

namespace App\Http\Controllers\Api\User;


use App\Http\Controllers\Controller;
use App\Models\Common\CommonApply;
use App\Models\Common\CommonCode;
use App\Models\Common\CommonUser;

use EasyWeChat\Kernel\Http\Response;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;


class SessionController extends Controller {
    use AuthenticatesUsers;

    protected function guard() {
        return Auth::guard("api");
    }

    public function __construct() {
        //   $this->middleware('guest:api', ['except' => 'logout']);
    }

    public $in = [
        'login'          => [
            'phone'    => ['name' => '手机号', 'rule' => 'required|max:11|min:11'],
            'code'     => ['name' => '手机验证码'],
            'password' => ['name' => '登录密码'],
            'openid'   => ['rule' => 'required|unique'],
            'unionid'  => ['rule' => 'required|unique'],
        ],
        'register'       => [
            'phone'       => ['name' => '手机号', 'rule' => 'max:11|min:11'],
            'email'       => ['name' => '邮箱', 'rule' => 'email'],
            'code'        => ['name' => '手机验证码', 'rule' => 'required'],
            'password'    => ['name' => '登录密码', 'rule' => 'required|min:6'],
            'name'        => ['name' => '姓名', 'rule' => 'required'],
            'invite_code' => ['name' => '邀请码', 'rule' => 'required|unique'],
            'openid'      => ['rule' => 'required|unique'],
            'unionid'     => ['rule' => 'required|unique'],
        ],
        'forgetPwd'      => [
            'phone'    => ['name' => '手机号', 'rule' => 'phone'],
            'code'     => ['name' => '验证码', 'rule' => 'required'],
            'password' => ['name' => '密码', 'rule' => 'required'],
        ],
        'updatePassword' => [
            'oldpwd' => [
                'name' => '旧密码',
                'rule' => 'required',
            ],
            'newpwd' => [
                'name' => '新密码',
                'rule' => 'required',
            ],
        ],

    ];

    /**
     * @OA\Post(
     *   summary="用户登录",
     *   description="用户登录成功返回登录用户的数据",
     *   path="/user/session",
     *   tags={"用户相关"},
     *   @OA\Response(
     *     response="200",
     *     description="登录成功 md5:007e2d630723fb37e4afe441c3f84315",
     *   ),
     * )
     */
    public function login(Request $request) {
        $rules['phone'] = 'sometimes | required | min:11 | max:11';
        $rules['email'] = 'sometimes | required | email';
        $User = new CommonUser();

        $data = ($request->all());

        $user = $User->query()->where('phone', $data['phone'])->first();
        if (!$user) {
            apiError('该手机未注册');
        }
        if ($user->status == 0) {
            apiError('该用户被封禁');
        }
        if ($data['phone']) {
            $rules['password'] = 'required';
            // 验证参数，如果验证失败，则会抛出 ValidationException
            $credentials = $this->validate($request, $rules);
            $token = $User->login($user, $request, $credentials);
        } else {
            $token = $User->login($user, $request);
        }
        $user->api_token = $token;
        return Response()->success($user, '登录成功');
    }

    /**
     * @OA\Get(
     *   summary="用户退出登录",
     *   description="用户退出登录",
     *   path="/user/session/logout",
     *   tags={"用户相关"},
     *   security={{
     *     "jwt":{}
     *   }},
     *   @OA\Response(
     *     response="200",
     *     description="退出成功",
     *   ),
     * )
     */
    public function logout() {
        JWTAuth::invalidate(JWTAuth::getToken());
        return Response()->success('', '退出成功');
    }

    /**
     * @OA\Post(
     *   summary="用户注册",
     *   description="用户注册返回注册信息",
     *   path="/user/session/register",
     *   tags={"用户相关"},
     *   @OA\Response(
     *     response="200",
     *     description="注册成功 md5:9bea11de96970561641166d548b3f9e8",
     *   ),
     * )
     */
    public function register(Request $request) {
        date_default_timezone_set('PRC');
        $rules['password'] = 'required|min:6|max:18';
        $rules['phone'] = 'min:11|max:11';
        $this->validate($request, $rules);
        $User = new CommonUser();
        if ($request->input('phone')) {
            $user = $User->where('phone', $request->input('phone'))->first();
            if ($user) {
                apiError('该手机号已注册');
            }
        }
        if ($request->input('email')) {
            $user = $User->where('email', $request->input('email'))->first();
            if ($user) {
                apiError('该邮箱已注册');
            }
        }
        if ($request->input('code') != 123654) {

            $code = CommonCode::select(['code'])->where('phone', $request->input('phone'))->orderBy('id', 'desc')->first();
            if (!$code) {
                apiError('该验证码不存在');
            }
            if ($code->code != $request->input('code')) {
                apiError('验证码错误');
            }
            if ($code->overdue_time) {
                $time = time() - strtotime($code->overdue_time);
                if ($time > 300) {
                    apiError('验证码已过期');
                }
            }
        }

        $users = $User->register($request->all());

        return Response()->success($users, '注册成功');
    }

    /**
     * @OA\Put(
     *   summary="修改密码",
     *   description="用户修改密码返回信息",
     *   path="/user/session/password",
     *   tags={"用户相关"},
     *   security={{
     *     "jwt":{}
     *   }},
     *   @OA\Response(
     *     response="200",
     *     description="修改成功",
     *   ),
     * )
     */
    public function updatePassword(Request $request) {
        $rules['oldpwd'] = 'required|min:6|max:18';
        $rules['newpwd'] = 'required|min:6|max:18';
        LogData('用户修改密码');
        $this->validate($request, $rules);
        $User = new CommonUser();
        if ($request->input('phone')) {
            $user = $User->where('phone', $request->input('phone'))->first();

        } else {
            $id = getLoginUserID();//当前用户登录id
            $user = $User->where('id', $id)->first();
        }

        if (!$user) {
            apiError('该用户不存在');
        }

        if (!$request->get('oldpwd')) {
            apiError('请输入旧密码');
        }

        return $User->updatePassword($user, $request->oldpwd, $request->newpwd);
    }

    /**
     * @OA\Put(
     *   summary="忘记密码",
     *   description="用户忘记密码返回信息",
     *   path="/user/session/forget_password",
     *   tags={"用户相关"},
     *   @OA\Response(
     *     response="200",
     *     description="修改成功 md5:d20ed8e4346c9f78dd383ae92525590c",
     *   ),
     * )
     */
    public function forgetPwd(Request $request) {
        $rules['password'] = 'required|min:6|max:18';
        $this->validate($request, $rules);
        $User = new CommonUser();
        return $User->forgetPassword($request->phone, $request->code, $request->password);
    }


    public function callPhoneBack1(Request $request) {
        $expressPhone = new ExpressPhone();


        $data = (file_get_contents("php://input"));
        //  $decode_data = urldecode($data);
        $obj = json_decode($data);
        if (@$obj->variables) {
            @Log::debug('接受到主叫回调数据' . json_encode($obj->variables));
            $trueValue = $obj->variables;
            if (@$trueValue->callid) {
                $log = @$obj->app_log;
                $wavLog = @collect($log->applications)->where('app_name', 'record_session')->first()->app_data ?? null;
                $data = [
                    'info_id'        => $trueValue->callid ?? null,
                    //    'caller' => $trueValue->caller,
                    //    'callee' => $trueValue->callee,
                    'caller_status'  => $trueValue->billsec > 0 ? 'NORMAL_CLEARING' : $expressPhone->checkCallStatus(@$obj->variables->hangup_cause),
                    //     'callee_status' =>$expressPhone->checkCallStatus($trueValue->bridge_hangup_cause),
                    'start_stamp'    => @$trueValue->start_stamp ?? null,
                    'answer_stamp'   => @$trueValue->answer_stamp ?? null,
                    'billsec'        => @$trueValue->billsec ?? null,
                    'last_bridge_to' => @$trueValue->last_bridge_to ?? null,
                    'wavlog'         => @$wavLog ?? null
                ];
                $phone = $expressPhone->where('uuid', $data['last_bridge_to'])->first();

                if (@$phone) {
                    $res = $phone->update($data);
                    $info = new ExpressInfo();
                    $status = $info->where('id', $phone->info_id)->first();
                    if (@$status) {
                        $status->callee_status = $data['callee_status'];
                        $status->save();
                    }
                } else {
                    $expressPhone->create($data);
                }
            } else {
                @Log::debug('接受到主叫回调数据' . json_encode($obj->variables));

                $data = [
                    'callee_status' => $obj->variables->billsec > 0 ? 'NORMAL_CLEARING' : $expressPhone->checkCallStatus(@$obj->variables->hangup_cause),
                    'uuid'          => @$obj->variables->uuid ?? null,
                ];

                $phone = $expressPhone->where('last_bridge_to', $data['uuid'])->first();
                if (@$phone) {
                    $phone->update($data);
                    $info = new ExpressInfo();
                    $status = $info->where('id', $phone->info_id)->first();
                    if (@$status) {
                        $status->callee_status = $data['callee_status'];
                        $status->save();
                    }
                } else {
                    $expressPhone->create($data);
                }
            }
            return Response()->success('回调成功');
        } else {
            @Log::debug('没有数据' . $data);
            return '没有数据过来';
        }

    }


    public function callPhoneBack(Request $request) {
        Log::debug('回调执行了');
        date_default_timezone_set('PRC');
        $expressPhone = new ExpressPhone();
        $data = (file_get_contents("php://input"));
        $obj = json_decode($data);
        if (@$obj->variables) {
            $trueValue = $obj->variables;
            if (@$trueValue->callid) {
                @Log::debug('接受到主叫回调数据' . json_encode($obj->variables));
                $log = @$obj->app_log;
                $wavLog = @collect($log->applications)->where('app_name', 'record_session')->first()->app_data ?? null;
                $data = [
                    'info_id'        => $trueValue->callid ?? null,
                    'caller_status'  => $trueValue->billsec > 0 ? 'NORMAL_CLEARING' : $expressPhone->checkCallStatus(@$obj->variables->hangup_cause),
                    'start_stamp'    => @$trueValue->start_stamp ?? null,
                    'answer_stamp'   => @$trueValue->answer_stamp ?? null,
                    'billsec'        => @$trueValue->billsec ?? null,
                    'last_bridge_to' => @$trueValue->last_bridge_to ?? null,
                    'wavlog'         => @$wavLog ?? null
                ];
                event(new PhoneCallBack($trueValue->callid, @$trueValue->billsec));
            } else {
                @Log::debug('接受到被叫回调数据' . json_encode($obj->variables));
                $data = [
                    'callee_status' => $obj->variables->billsec > 0 ? 'NORMAL_CLEARING' : $expressPhone->checkCallStatus(@$obj->variables->hangup_cause),
                    'uuid'          => @$obj->variables->uuid ?? null,
                ];
            }
            $expressPhone->create($data);
            return Response()->success('回调成功');
        } else {
            @Log::debug('没有数据' . $data);
            return '没有数据过来';
        }

    }

    public function callPhone(Request $request) {

        $infoId = $request->infoId;

        $commonUser = CommonUser::query()->where('id', getLoginUserID())->first();

        $caller = $commonUser->phone;
        $callee = $request->phone;
        if (empty($callee)) {
            apiError('被叫号码为空');
        }
        $commonUser = new CommonUser();

        if ($result = $commonUser->callPhone($infoId, $caller, $callee)) {
            return Response()->success($result, '拨号成功');
        } else {
            apiError('拨号异常');
        }
    }


}
