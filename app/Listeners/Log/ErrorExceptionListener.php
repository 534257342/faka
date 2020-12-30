<?php
/**
 * Created by PhpStorm.
 * User: xiao
 * Date: 2018/12/11
 * Time: 下午4:21
 */

namespace App\Listeners\Log;



use App\Events\Log\ErrorException;
use App\Models\System\SystemException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Debug\Exception\FlattenException;

class ErrorExceptionListener
{

    public function handle(ErrorException $exc)
    {

        $exception=$exc->exception;

    /*    $data=[
             'message' => $exception->getMessage(),
             'router'=>request()->getRequestUri(),
             'action'=>request()->route()->getActionName();
        ];
   */
        $e = FlattenException::create($exception);
        $excMessage = $e->toArray()[0];
        $data=[
            'message' => $excMessage['message'],
            'trace' =>(json_encode($excMessage['trace'], JSON_UNESCAPED_UNICODE)),
            'user'=>getLoginUserID()??0,
            'router'=>request()->getRequestUri(),
        ];
        $systemException = new SystemException();
        if (!$systemException->create($data)) {
            Log::error('异常信息错误录入失败');
            throw  new \Exception('数据录入失败');
        }else{
            return true;
        }

    }


}