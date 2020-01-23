<?php

namespace AdminEshop\Contracts\Payments;

class PaymentHelper
{
    private $payment;

    private $paymentMethod;

    private $order;

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod($method)
    {
        $this->paymentMethod = $method;

        return $this;
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function setPayment($payment)
    {
        $this->payment = $payment;

        return $this;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /*
     * Get order payment hash
     */
    public function getOrderHash($type = null)
    {
        $order = $this->getOrder();

        return sha1(md5(sha1(md5(env('APP_KEY').$order->payment_method_id.$order->getKey().$type))));
    }
}

?>