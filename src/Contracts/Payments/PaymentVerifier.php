<?php

namespace AdminEshop\Contracts\Payments;

use AdminEshop\Contracts\Payments\Concerns\PaymentErrorCodes;
use AdminEshop\Contracts\Payments\Exceptions\PaymentResponseException;
use AdminEshop\Contracts\Payments\PaymentHelper;
use AdminEshop\Models\Orders\Payment;
use OrderService;
use Exception;

class PaymentVerifier
{
    private $provider;
    private $payment;

    public function __construct(PaymentHelper $provider, Payment $payment)
    {
        $this->provider = $provider;
        $this->payment = $payment;
    }

    public function verifyPaidStatus(callable $success, callable $error)
    {
        //Check if order is paid or throw ErrorPaymentException
        try {
            $this->provider->isPaid($this->provider->getPaymentId());

            //Update payment status
            $this->payment->update([ 'status' => 'paid' ]);

            return $success();
        } catch (Exception $e) {
            if ( OrderService::isDebug() ){
                throw $e;
            }

            if ( $e instanceof PaymentResponseException ) {
                $errorCode = PaymentErrorCodes::CODE_PAYMENT_UNVERIFIED;
            } else {
                $errorCode = PaymentErrorCodes::CODE_ERROR;
            }

            return $error($errorCode, $e);
        }
    }
}