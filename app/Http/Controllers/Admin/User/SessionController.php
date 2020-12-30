<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\Common\CommonCode;
use App\Models\Common\CommonUser;
use EasyWeChat\Kernel\Http\Response;
use Illuminate\Database\Schema\Builder;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class SessionController extends Controller
{
    use AuthenticatesUsers;

    protected function guard()
    {
        return Auth::guard("api");
    }

    public function __construct()
    {
     //   $this->middleware('guest:api', ['except' => 'logout']);
    }
    public $in = [
        'login'=>[
            'phone'=>['name'=>'手机号','rule'=>'required|max:11|min:11|phone'],
            'code'=>['name'=>'手机验证码'],
            'password'=>['name'=>'登录密码'],
            'openid'=>['rule'=>'required|unique'],
            'unionid'=>['rule'=>'required|unique'],
        ],
        'register'=>[
            'phone'=>['name'=>'手机号','rule'=>'phone|max:11|min:11'],
            'email'=>['name'=>'邮箱','rule'=>'email'],
            'code'=>['name'=>'手机验证码','rule'=>'required'],
            'password'=>['name'=>'登录密码','rule'=>'required|min:6'],
            'name'=>['name'=>'姓名','rule'=>'required'],
            'invite_code'=>['name'=>'邀请码','rule'=>'required|unique'],
            'openid'=>['rule'=>'required|unique'],
            'unionid'=>['rule'=>'required|unique'],
        ],
        'forgetPwd'=>[
            'phone'=>['rule'=>'phone'],
            'code'=>['rule'=>'required'],
        ],
        'updatePassword'=>[
            'oldpwd'=>[
                'name'=>'旧密码',
                'rule'=>'required',
            ],
            'newpwd'=>[
                'name'=>'新密码',
                'rule'=>'required',
            ],
        ],
        'weChatLogin'=>[
            'unionid'=>[]
        ]
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
    public function login(Request $request)
    {
        $rules['phone'] = 'required | min:11 | max:11';
        $User = new CommonUser();
        $user = $User->query()->where('phone',$request->phone)->first();
        if(!$user){
            apiError('该手机未注册');
        }
        if( $user->postking != 1){
            apiError('该用户不是管理员');
        }
        if($request->input('password')){
            $rules['password'] = 'required';
            $credentials = $this->validate($request, $rules);
            $token = $User->login($user,$request,$credentials);
        }else{
            $token = $User->login($user,$request);
        }
        $user->api_token = $token;
        return Response()->success($user,'登录成功');
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
    public function logout(){
        JWTAuth::invalidate(JWTAuth::getToken());
        return Response()->success('','退出成功');
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
    public function register(Request $request)
    {
        $rules['password'] = 'required|min:6|max:18';
        $rules['phone'] = 'min:11|max:11';
        $this->validate($request, $rules);
        $User = new CommonUser();
        if($request->input('phone')){
            $user = $User->where('phone',$request->input('phone'))->first();
            if($user){
                apiError('该手机号已注册');
            }
        }
        if($request->input('email')){
            $user = $User->where('email',$request->input('email'))->first();
            if($user){
                apiError('该邮箱已注册');
            }
        }

        $users = $User->register($request->all());

        return Response()->success($users,'注册成功');
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
    public function updatePassword(Request $request){
        $rules['newpwd'] = 'required|min:6|max:18';
        $rules['oldpwd'] = 'required|min:6|max:18';
        $this->validate($request, $rules);
        $id = getLoginUserID();//当前用户登录id
        $User = new CommonUser();
        $user = $User->where('id',$id)->first();
        if(!$user){
            apiError('该用户不存在');
        }
        if(!$request->get('oldpwd')){
            apiError('请输入旧密码');
        }
        //->updatePassword($user,$request->oldpwd,$request->newpwd);
        $testPwd = password_verify($request->oldpwd,$user->password);//验证加密的密码
        if($testPwd){
            if(!$request->newpwd){
                apiError('请输入新密码');
            }
            $user->password = $request->newpwd;
            $users = $user->save();
            if($users){
                JWTAuth::invalidate(JWTAuth::getToken());//清除token
                return Response()->success($users,'修改成功');
            }else{
                apiError('修改密码失败');
            }
        }else{
            apiError('原密码错误,请重新输入');
        }
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
    public function forgetPwd(Request $request){
        $rules['password'] = 'required|min:6|max:18';
        $this->validate($request, $rules);
        $User = new CommonUser();
        return $User->forgetPassword($request->phone,$request->code,$request->password);
    }



}
