<?php

namespace AdminEshop\Contracts\Order;

use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Orders\Order;
use AdminEshop\Models\Store\PaymentsMethod;
use AdminPayments\Contracts\ConfigProvider;
use Arr;

class OrderConfigProvider extends ConfigProvider
{
    protected $delivery;

    public function setDelivery($delivery)
    {
        $this->delivery = $delivery;

        return $this;
    }

    public function getDelivery()
    {
        return $this->delivery;
    }
}