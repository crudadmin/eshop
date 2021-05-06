<?php

namespace AdminEshop\Contracts\Payments;

use Log;
use AdminEshop\Contracts\Order\OrderProvider;

class PaymentHelper extends OrderProvider
{
    private $payment;

    private $response;

    public function getPayment()
    {
        return $this->payment;
    }

    public function setPayment($payment)
    {
        $this->payment = $payment;

        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /*
     * Get order payment hash
     */
    public function getOrderHash($type = null)
    {
        return $this->getOrder()->makePaymentHash($type);
    }

    public function getResponseUrl($type)
    {
        return action('\AdminEshop\Controllers\Payments\PaymentController@paymentStatus', [
            $this->getPayment()->getKey(),
            $type,
            $this->getOrderHash($type),
        ]);
    }
}

?>