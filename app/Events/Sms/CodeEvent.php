<?php

namespace App\Events\Sms;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CodeEvent {
    use Dispatchable, InteractsWithSockets, SerializesModels;
    /**
     * @var string 手机号
     */
    protected $phone;
    /**
     * @var string 手机验证码
     */
    protected $type;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($phone, $type = 'smsCode') {
        $this->phone = $phone;
        $this->type = $type;
    }


    public function getPhone() {
        return $this->phone;
    }

    public function getType() {
        return $this->type;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn() {
        return new PrivateChannel('channel-name');
    }
}
