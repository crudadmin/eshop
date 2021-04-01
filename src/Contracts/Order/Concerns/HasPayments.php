<?php

namespace AdminEshop\Contracts\Order\Concerns;

use AdminEshop\Contracts\Payments\GopayPayment;
use Exception;
use Log;

trait HasPayments
{
    protected $onPaymentSuccessCallback = null;
    protected $onPaymentErrorCallback = null;

    /**
     * Return all registred payment providers. Key in array belongs to id in table payments_methods and value is represented
     * as payment provider
     *
     * @return  array
     */
    public function getPaymentProviders()
    {
        return config('admineshop.payment_providers', []);
    }

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

    public function onPaymentError()
    {
        $order = $this->getOrder();

        //Log wrong payment
        if ( $order ){
            $order->log()->create([
                'type' => 'error',
                'code' => 'payment-canceled',
            ]);
        }

        if ( is_callable($callback = $this->onPaymentErrorCallback) ) {
            return $callback($order);
        }
    }

    public function hasOnlinePayment($paymentMethodId = null)
    {
        return $this->getPaymentClass($paymentMethodId) ? true : false;
    }

    public function getPaymentClass($paymentMethodId = null)
    {
        $providers = $this->getPaymentProviders();

        $order = $this->getOrder();

        $paymentMethodId = $paymentMethodId ?: $order->payment_method_id;

        if ( array_key_exists($paymentMethodId, $providers) ) {
            $paymentClass = new $providers[$paymentMethodId];

            return $paymentClass->setOrder($order)
                                ->setPaymentMethod($order->payment_method);
        }
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

    public function getPaymentRedirect($paymentMethodId = null)
    {
        $payment = $this->makePayment($paymentMethodId);

        $paymentClass = $this->getPaymentClass($paymentMethodId);
        $paymentClass->setPayment($payment);

        try {
            return $paymentClass->getPaymentUrl();
        } catch (Exception $e){
            $this->logPaymentError($e);
        }
    }
}
?>