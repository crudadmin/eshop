<?php

namespace AdminEshop\Contracts\Payments;

use AdminEshop\Contracts\Order\OrderProvider;

class PaymentHelper extends OrderProvider
{
    private $payment;

    public function getPayment()
    {
        return $this->payment;
    }

    public function setPayment($payment)
    {
        $this->payment = $payment;

        return $this;
    }

    /*
     * Get order payment hash
     */
    public function getOrderHash($type = null)
    {
        return $this->getOrder()->makePaymentHash($type);
    }
}

?>