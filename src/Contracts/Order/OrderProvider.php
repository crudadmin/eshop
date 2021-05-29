<?php

namespace AdminEshop\Contracts\Order;

use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Orders\Order;
use AdminEshop\Models\Store\PaymentsMethod;
use Arr;

class OrderProvider
{
    protected $options = [];

    protected $order;

    protected $paymentMethod;

    protected $delivery;

    /**
     * Constructing of order provider
     *
     * @param  mixed  $options
     */
    public function __construct($options = null)
    {
        $this->options = $options;
    }

    public function setOrder(Order $order = null)
    {
        $this->order = $order;

        return $this;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($key)
    {
        $options = $this->options ?: [];

        return Arr::get($options, $key);
    }

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }
}