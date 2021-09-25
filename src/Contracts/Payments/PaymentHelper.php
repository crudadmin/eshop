<?php

namespace AdminEshop\Contracts\Payments;

use AdminEshop\Contracts\Order\OrderProvider;
use AdminEshop\Contracts\Payments\Exceptions\PaymentResponseException;

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

    /**
     * Set received payment id of created payment from provider
     *
     * @param  string|int  $paymentId
     */
    public function setPaymentId($paymentId)
    {
        $this->getPayment()->update([
            'payment_id' => $paymentId,
        ]);
    }

    /**
     * Get created payment ID from provider.
     * It is more secure to set received payment id from provider, and then use this number from database.
     * Because if someone would change ?code or ?id parameter returned from payment, they make fake paid payment.
     */
    public function getPaymentId()
    {
        return $this->getPayment()->payment_id;
    }

    /*
     * Get order payment hash
     */
    public function getOrderHash($type = null)
    {
        return $this->getOrder()->makePaymentHash($type);
    }

    /**
     * Returns additional payment data
     *
     * @param  mixed  $paymentResponse
     *
     * @return  array
     */
    public function getPaymentData($paymentResponse)
    {
        return [
            'url' => $paymentResponse,
        ];
    }

    /**
     * Returns payment url from payment response
     *
     * @param  mixed  $paymentResponse
     *
     * @return  string|null
     */
    public function getPaymentUrl($paymentResponse)
    {
        return $paymentResponse;
    }

    public function getResponseUrl($type)
    {
        return action('\AdminEshop\Controllers\Payments\PaymentController@paymentStatus', [
            $this->getPayment()->getKey(),
            $type,
            $this->getOrderHash($type),
        ]);
    }

    public function getPostPaymentUrl($paymentResponse)
    {
        $type = 'postpayment';

        return action('\AdminEshop\Controllers\Payments\PaymentController@postPayment', [
            $this->getOrder()->getKey(),
            $this->getOrderHash($type),
        ]);
    }

    /**
     * On payment paid successfully
     */
    // public function onPaid(){}
}

?>