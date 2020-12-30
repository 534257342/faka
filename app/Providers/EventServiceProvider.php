<?php

namespace App\Providers;


use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [

        //监听日志
        'App\Events\Log\ModelChange'=>[
            'App\Listeners\Log\ModelChange',
        ],

        //验证码事件
        'App\Events\Sms\CodeEvent' => [
            'App\Listeners\Sms\CodeListener',

        ],
        //验证码事件
        'App\Events\Sms\SmsEvent' => [
            'App\Listeners\Sms\SmsListener',

        ],

        'App\Events\Excel\InfoExcel' => [
            'App\Listeners\Excel\InfoExcelListener',
        ],

        'App\Events\Phone\PhoneCallBack' => [
            'App\Listeners\Phone\PhoneCallBackListener',
        ],

        'App\Events\Phone\PhoneCallBefore' => [
            'App\Listeners\Phone\PhoneCallBeforeListener',
        ],

        'App\Events\Xml\XmlInfo' => [
            'App\Listeners\Xml\XmlBuildListener',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
