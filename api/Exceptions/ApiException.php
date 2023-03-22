<?php

namespace Api\Exceptions;

use Exception;

class ApiException extends Exception
{
    public $message;
    public $code;
    public $status;
    
    public function __construct($message, $code, $status = 500)
    {
        $this->message = $message;
        $this->code = $code;
        $this->status = $status;
        parent::__construct($message);
    }
}
