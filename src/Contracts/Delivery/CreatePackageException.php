<?php

namespace AdminEshop\Contracts\Delivery;

use Exception;

class CreatePackageException extends Exception
{
    protected $message;
    protected $response;

    public function __construct($message, $response = null)
    {
        $this->message = $message;

        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }
}