<?php

namespace AdminEshop\Contracts\Delivery;

class ShippingResponse
{
    protected $data = [];

    protected $shippingId;

    protected $success;

    protected $message;

    public function __construct(bool $success, $shippingId = null, string $message = null, $data = [])
    {
        $this->success = $success;
        $this->shippingId = $shippingId;
        $this->message = $message;
        $this->data = $data;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function shippingId()
    {
        return $this->shippingId;
    }

    public function isSuccess()
    {
        return $this->success ? true : false;
    }

    public function getData()
    {
        return $this->data;
    }
}