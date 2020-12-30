<?php

namespace App\Listeners\SendMail;

use App\Events\SendMail\SendMail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendMailListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(SendMail $event)
    {
        Mail::send('emails/exception', ['content' =>$event->content], function ($message)use($event) {
            //在邮件中上传附件
            if(is_array($event->url)){
                for ($i=0;$i<count($event->url);$i++){
                    $message->attach($event->url[$i]);
                }
            }else{
                $message->attach($event->url);
            }
            $to = $event->email;
            $mails = explode(',', $to);
            foreach ($mails as $mail) {
                $message->to($mail)->subject('测试发送邮件');
            }
        });
    }
}
