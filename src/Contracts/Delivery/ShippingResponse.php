<?php

namespace AdminEshop\Contracts\Delivery;

class ShippingResponse
{
    protected $data = [];

    protected $shippingId;

    protected $message;

    protected $label;

    public function __construct($shippingId, string $message = null, $data = [])
    {
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

    public function getData()
    {
        return $this->data;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($data, $extension = 'pdf')
    {
        $this->label = [
            'data' => $data,
            'extension' => $extension,
        ];
    }
}