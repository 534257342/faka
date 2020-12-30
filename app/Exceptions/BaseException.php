<?php

namespace App\Exceptions;

use Larfree\Exceptions\ApiException;
//use \Exception as ApiException;

class BaseException extends ApiException
{
    public function __construct($message = '', $code = 422, $data = [], \Throwable $previous = null)
    {
        parent::__construct($message, $data, $code, $previous);
//        parent::__construct($message, $code, $previous);
    }
}
