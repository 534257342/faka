<?php
/**
 * Created by PhpStorm.
 * User: xiao
 * Date: 2018/12/11
 * Time: 下午4:17
 */

namespace App\Events\Log;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;

class ErrorException  extends Event
{
    use SerializesModels;
    public  $exception;

    public function __construct($exception)
    {
        $this->exception = $exception;
    }

}