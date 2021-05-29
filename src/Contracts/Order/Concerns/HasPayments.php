<?php

namespace AdminEshop\Contracts\Order\Concerns;

use Admin;
use AdminEshop\Contracts\Payments\Concerns\PaymentErrorCodes;
use AdminEshop\Contracts\Payments\GopayPayment;
use Exception;
use Log;

trait HasPayments
{
    protected $paymentTypesConfigKey = 'admineshop.payment_providers';

    protected $onPaymentSuccessCallback = null;
    protected $onPaymentErrorCallback = null;

    public function setOnPaymentSuccess(callable $callback)
    {
        $this->onPaymentSuccessCallback = $callback;
    }

    public function setOnPaymentError(callable $callback)
    {
        $this->onPaymentErrorCallback = $callback;
    }

    public function onPaymentSuccess()
    {
        $order = $this->getOrder();

        if ( is_callable($callback = $this->onPaymentSuccessCallback) ) {
            return $callback($order);
        }
    }

    /**
     * Get redirect link with payment error code
     *
     * @param  int  $code
     *
     *
     * @return  string|nullable
     */
    public function onPaymentError(int $code = 1)
    {
        $order = $this->getOrder();

        if ( is_callable($callback = $this->onPaymentErrorCallback) ) {
            $message = PaymentErrorCodes::getMessage($code);

            return $callback($order, $code, $message);
        }
    }

    public function hasOnlinePayment($paymentMethodId = null)
    {
        return $this->getPaymentClass($paymentMethodId) ? true : false;
    }

    public function getPaymentClass($paymentMethodId = null)
    {
        $order = $this->getOrder();

        if ( !($paymentMethodId = $paymentMethodId ?: $order->payment_method_id) ){
            return;
        }

        $paymentClass = $this->getProviderById($this->paymentTypesConfigKey, $paymentMethodId);

        return $paymentClass;
    }

    /*
     * Create order payment
     */
    public function makePayment($paymentMethodId = null)
    {
        $order = $this->getOrder();

        return $order->payments()->create([
            'price' => $order->price_vat,
            'payment_method_id' => $paymentMethodId ?: $order->payment_method_id,
            'uniqid' => uniqid().str_random(10),
        ]);
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

    public function bootPaymentClass($paymentMethodId)
    {
        if ( !$this->hasOnlinePayment($paymentMethodId) ){
            return false;
        }

        return Admin::cache('payments.'.$paymentMethodId.'.data', function() use ($paymentMethodId) {
            try {
                $payment = $this->makePayment($paymentMethodId);

                $paymentClass = $this->getPaymentClass($paymentMethodId);

                $paymentClass->setPayment($payment);

                $paymentClass->setResponse(
                    $paymentClass->getPaymentResponse()
                );

                return $paymentClass;
            } catch (Exception $e){
                $this->logPaymentError($e);
            }
        });
    }
}
?>