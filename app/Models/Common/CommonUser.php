<?php

namespace App\Models\Common;


use App\Exceptions\Order\OrderCompleteFailedException;

use Larfree\Models\ApiUser;

use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Event;
use App\Scopes\Common\CommonUserScope;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;


class CommonUser extends ApiUser implements JWTSubject
{
    use Notifiable,
        SoftDeletes,
        CommonUserScope;

    protected  $hidden = ['password','remember_token'];



    public function setPasswordAttribute($value)
    {
        if($value)
            $this->attributes['password'] = password_hash($value,PASSWORD_DEFAULT);
        else
            unset($this->attributes['password']);
    }



    /**
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }



    public function login($user,$request,$credentials=''){

            try {
                if (!$token = JWTAuth::attempt($credentials)) {
                    apiError("账号或者密码错误");
                }
            } catch (JWTException $e) {
                apiError("token 无法生成");
            }
        return $token;
    }

    /**
     * @param $phone
     * @param $name
     * @param $password
     * @param $openid
     * @param $unionid
     * @param string $email
     * @return CommonUser|bool
     * 注册
     */
    public function register($data)
    {

        $newUser = [
            'phone' => $data['phone'],//手机号
            'password' => $data['password'],//密码

        ];

        if(isset($data['email'])){
            $newUser['email'] = $data['email'];
        }
        if(isset($data['name'])){
            $newUser['name'] = $data['name'];
            $newUser['nickname'] = $data['name'];
        }
        if(isset($data['openid'])){
            $newUser['openid'] = $data['openid'];
            $newUser['unionid'] = $data['unionid'];
        }


        DB::beginTransaction();
        try{
            $users = $this->create($newUser);
            if (!$users) {
                throw new \Exception("注册失败");
            }
            if(@$data['invite_code'] && $users){
               $result=Event::fire(new InviteCode($users->id,$data['invite_code']));
                if(!$result){
                    throw new \Exception("领劵失败");
                }
            }

            DB::commit();
        } catch (\Exception $e){
            DB::rollback();//事务回滚
            return apiError($e->getMessage());

        }
        $token = JWTAuth::fromUser($users);
        $users->api_token = $token;
        return $users;
    }

    /**
     * @param $user
     * @param $oldpwd
     * @param $newpwd
     * @return mixed
     * @throws \Larfree\Exceptions\ApiException
     * 修改密码
     */
    public function updatePassword($user,$oldpwd,$newpwd){
        $testPwd = password_verify($oldpwd,$user->password);//验证加密的密码
        if($testPwd){
            $user->password = $newpwd;
            $users = $user->save();
            if($users){
                return $user->makeHidden('password');
            }else{
                apiError('修改密码失败');
            }
        }else{
            apiError('原密码错误,请重新输入');
        }
    }


    public function forgetPassword($phone,$code,$password){
        $user = $this->where('phone',$phone)->first();
        if(!$user){
            apiError('该手机未注册');
        }
        if($code != '123654'){//添加一个万能验证码(临时)
            $codes = CommonCode::where('phone',$phone)->orderBy('id','desc')->first();
            if(!$codes){
                apiError('查无此验证码');
            }
            if($codes->code != $code){
                apiError('验证码错误');
            }
            if($codes->overdue_time){
                $time = time()-strtotime($codes->overdue_time);
                if($time>300){

                    apiError('验证码已过期');
                }
            }
        }

        $user->password = $password;
        $updateUser = $user->save();
        if($updateUser){
            return $user;
        }else{
            apiError('忘记密码失败');
        }
    }





    public function apply()
{
    return $this->hasMany(CommonApply::class,'user_id','id');

}


    public function getCurrentPositionAttribute()
    {
        $key = 'driver_position_' . $this->getAttributeValue('id');

        if ($position = Redis::get($key))
        {
            return json_decode($position, true);
        }

        return null;
    }

    /**
     * @return string
     * 处理当用户没有名字就把给用户赋值
     */
    public function getNameAttribute($value)
    {
        if(!$value){
            return '拨号员'.@$this->getAttribute('id');
        }
        return $value;
    }


}
