<?php

namespace AdminEshop\Contracts\Order;

use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Orders\Order;
use AdminEshop\Models\Store\PaymentsMethod;

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

    public function setDelivery(Delivery $delivery = null)
    {
        $this->delivery = $delivery;

        return $this;
    }

    public function setPaymentMethod(PaymentsMethod $method = null)
    {
        $this->paymentMethod = $method;

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

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }
}