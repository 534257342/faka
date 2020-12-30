<?php
/**
 * Larfree Api类
 * @author xiao
 */
namespace App\Http\Controllers\Api\Common;
use Illuminate\Http\Request;
use Larfree\Controllers\ApisController as Controller;
use App\Models\Common\CommonCode;
class CodeController extends Controller
{
    public function __construct(CommonCode $model)
    {
        $this->model = $model;
        parent::__construct();
    }

    public function verificationCode(Request $request){

        if(!$request->input('phone')){

            apiError('请输入手机号');

        }

        if(!$request->input('code')){

            apiError('请输入验证码');

        }

        $code = $this->model->where('phone',$request->phone)->orderBy('id','desc')->first();

        if(!$code){

            apiError('查无此验证码');
        }
        if($code->code != $request->code){
            apiError('无此验证码');
        }

        if($code->overdue_time){
            $time = time()-strtotime($code->overdue_time);
            if($time>300){

                apiError('验证码已过期');
            }
        }
        return $code;
    }
}