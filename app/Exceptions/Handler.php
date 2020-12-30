<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * 确定异常是否在“不报告”列表中。
     *
     * @param \Exception $exception
     *
     * @return bool
     */
    protected function shouldntReport(Exception $exception)
    {
        $dontReport = array_merge($this->dontReport, [HttpResponseException::class]);
        foreach ($dontReport as $type) {
            if ($exception instanceof $type) {
                return true;
            }
        }
        return false;
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if(method_exists($exception,'getCode')){
            $code=($exception->getCode());
            if($code==0){
                if(method_exists($exception,'getStatusCode')){
                    $code=($exception->getStatusCode());
                }
            }
        }else{
            $code=($exception->getStatusCode());
        }

        $enableEmailExceptions = config('exceptions.emailExceptionEnabled');

        if ($enableEmailExceptions === "") {
            $enableEmailExceptions = config('exceptions.emailExceptionEnabledDefault');
        }


        try {

            if ($enableEmailExceptions && $this->shouldReport($exception) && ($code >= 500 || $code==0)) {
                $this->logInMysql($exception);
            }
        } catch (Exception $e) {
            dump($e->getMessage());
        }

        //后台的跳转地址
        if ($exception->getMessage() == 'Unauthenticated.' && in_array('admin', $exception->guards())) {
            return redirect()->guest(route('admin.login'));
        }
        if ($exception->getMessage() == 'Unauthenticated.') {
            $json = ['code' => 401, 'status' => -10, 'msg' => '需要登录', 'data' => []];
            $response = response()->json($json);
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Authorization, Cookie, Accept');
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS, DELETE');
            $response->header('Access-Control-Allow-Credentials', 'false');
            return $response;
        }
        return parent::render($request, $exception);
    }



    public function sendEmail(Exception $exception)
    {

        $e = FlattenException::create($exception);
        $handler = new SymfonyExceptionHandler();
        $html = $handler->getHtml($e);
        Mail::send('emails/exception', ['content' => $html], function ($message) {
            $to = (config('mail.to'));
            $mails = explode(',', $to);
            foreach ($mails as $mail) {
                $message->to($mail)->subject('异常错误报告');
            }
        });
        //  Mail::send(new ExceptionOccured($html));


    }


    /**
     * 记录异常进入数据库
     * @param Exception $exception
     * @throws Exception
     */
    public  function  logInMysql(Exception $exception){
        event(new ErrorException($exception));
    }
}
