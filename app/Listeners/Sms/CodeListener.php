<?php

namespace App\Listeners\Sms;

use App\Events\Sms\CodeEvent;
use App\Events\Sms\SmsEvent;
use App\Models\Common\CommonCode;
use App\Models\Push\DaHanSms;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CodeListener
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
     * Handle the event.
     *
     * @param  CodeEvent  $event
     * @return void
     */
    public function handle(CodeEvent $event)
    {
        $Code = new CommonCode();

        $code = random_int(100000,999999);

        if($event->getType() == 'smsCode'){

            $Code->sendCode($event->getPhone(),$code,$event->getType());

        }else if($event->getType() == 'voiceCode'){

            $Code->sendVoiceCode($event->getPhone(),$code,$event->getType());
        }
    }
}
