<?php

namespace App\Listeners\Sms;

use App\Events\Sms\CodeEvent;
use App\Events\Sms\SmsEvent;
use App\Models\Common\CommonCode;
use App\Models\Push\DaHanSms;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SmsListener
{


    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * @param SmsEvent $event
     */
    public function handle(SmsEvent $event)
    {
        try{
            $DaHanSms = new DaHanSms();
            if($event->type == 'smsCode'){

                return $DaHanSms->sms($event->phone,$event->content);

            }else if($event->type == 'voiceCode'){
                return $DaHanSms->voiceCode($event->phone,$event->content);
            }
        }catch (\Exception $e){

            apiError($e->getMessage());
        }

    }
}
