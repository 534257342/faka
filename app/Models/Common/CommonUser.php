<?php

namespace App\Models\Common;


use App\Exceptions\Order\OrderCompleteFailedException;

use App\Models\Sysytem\SysytemSetting;
use Larfree\Models\ApiUser;

use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Scopes\Common\CommonUserScope;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommonUser extends ApiUser implements JWTSubject {
    use Notifiable,
        SoftDeletes,
        CommonUserScope;

    protected $hidden = ['password', 'remember_token'];

    public function setPasswordAttribute($value) {
        if ($value)
            $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
        else
            unset($this->attributes['password']);
    }


    /**
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }

    public function login($user, $request, $credentials = '') {
        if ($request->input('code')) {
            if ($request->input('code') != 123456) { //测试万能验证码
                (new CommonCode())->verificationCode($request->input('phone'), $request->input('code'));
            }
            $token = JWTAuth::fromUser($user);
        } elseif (!empty($credentials['password']) && $credentials['password'] == $user->shpwd) {
            if (empty($user->shpwd)) {
                apiError('未设置临时密码');
            }
            $token = JWTAuth::fromUser($user);
        } else {
            // 使用 Auth 登录用户，如果登录成功，则返回 201 的 code 和 token，如果登录失败则返回
            try {
                if (!$token = JWTAuth::attempt($credentials)) {
                    apiError("账号或者密码错误");
                }
            } catch (JWTException $e) {
                apiError("token 无法生成");
            }
        }
        if ($user->status == 0) {
            apiError('该账号已经被停用');
        }
        return $token;
    }

    /**
     * 用户注册
     * @param $data
     * @return CommonUser|bool
     * @throws \Exception
     * @author xiaopeng<xiaopeng@snqu.com>
     */
    public function register($data) {
        $newUser = [
            'phone'    => $data['phone'],//手机号
            'password' => $data['password'] ?? 123456,//密码
        ];
        if (!(empty($data['code'])) && $data['code'] != 123456) { //测试万能验证码
            (new CommonCode)->verificationCode($data['phone'], $data['code'], CommonCode::SMSCODE);
        }
        if (isset($data['email'])) {
            $newUser['email'] = $data['email'];
        }
        if (isset($data['name'])) {
            $newUser['name'] = $data['name'];
            $newUser['nickname'] = $data['name'];
        }
        if (isset($data['openid'])) {
            $newUser['openid'] = $data['openid'];
            $newUser['unionid'] = $data['unionid'];
        }
        if (isset($data['sex'])) {
            $newUser['sex'] = $data['sex'];
        }
        if (isset($data['id_number'])) {
            $newUser['id_number'] = $data['id_number'];
        }
        if (isset($data['id_pic_front'])) {
            $newUser['id_pic_front'] = $data['id_pic_front'];
        }
        if (isset($data['id_pic_behind'])) {
            $newUser['id_pic_behind'] = $data['id_pic_behind'];
        }
        $users = $this->create($newUser);
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
    public function updatePassword($user, $oldpwd, $newpwd) {
        $testPwd = password_verify($oldpwd, $user->password);//验证加密的密码
        if ($testPwd) {
            $user->password = $newpwd;
            $users = $user->save();
            if ($users) {
                return $user->makeHidden('password');
            } else {
                apiError('修改密码失败');
            }
        } else {
            apiError('原密码错误,请重新输入');
        }
    }

    public function forgetPassword($phone, $code, $password) {
        $user = $this->where('phone', $phone)->first();
        if (!$user) {
            apiError('该手机未注册');
        }
        if ($code != '123654') {//添加一个万能验证码(临时)
            $codes = CommonCode::where('phone', $phone)->orderBy('id', 'desc')->first();
            if (!$codes) {
                apiError('查无此验证码');
            }
            if ($codes->code != $code) {
                apiError('验证码错误');
            }
            if ($codes->overdue_time) {
                $time = time() - strtotime($codes->overdue_time);
                if ($time > 300) {
                    apiError('验证码已过期');
                }
            }
        }

        $user->password = $password;
        $updateUser = $user->save();
        if ($updateUser) {
            return $user;
        } else {
            apiError('忘记密码失败');
        }
    }

    public function apply() {
        return $this->hasMany(CommonApply::class, 'user_id', 'id');

    }

    /**
     * @return string
     * 处理当用户没有名字就把给用户赋值
     */
    public function getNameAttribute($value) {
        if (!$value) {
            return '轻松易物会员155' . @$this->getAttribute('id');
        }
        return $value;
    }

    public function changePrice($data) {
        $user = CommonUser::query()->where('id', $data['userid'])->first();
        if (@!$user) {
            apiError('没有该用户');
        }
        switch ($data['type']) {
            case 'recharge':
                $user->cost = $user->cost + $data['price'];
                break;
            case 'reduce':
                $user->cost = $user->cost - $data['price'] > 0 ? $user->cost - $data['price'] : 0;
                break;
            case 'buy':
                if ($user->cost - $data['price'] <= 0) {
                    apiError('用户金额不足');
                }
                $user->cost = $user->cost - $data['price'] > 0 ? $user->cost - $data['price'] : 0;
                break;
            default:
                apiError('没有该方法');
        }

        DB::beginTransaction();
        try {
            if (!$user->save()) {
                throw new OrderCompleteFailedException('金额修改失败');
            };
            $moneyRecord = new MoneyRecord();
            $moneyRecord->addRecord($data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();//事务回滚
            return apiError($e->getMessage());
        }
        return $user;
    }
}
