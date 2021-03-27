<?php

namespace AdminEshop\Contracts\Payments;

use Log;
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

    /**
     * Log error payment message
     *
     * @param  string|array  $log
     */
    public function logPaymentError($log = null)
    {
        //Serialize array error
        if ( is_array($log) ){
            $log = json_encode($log, JSON_PRETTY_PRINT);
        }

        Log::error($log);

        $this->getOrder()->log()->create([
            'type' => 'error',
            'code' => 'payment-error',
            'log' => $log,
        ]);
    }
}

?>