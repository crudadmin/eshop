<?php

namespace AdminEshop\Contracts\Order\Concerns;

use AdminEshop\Contracts\Payments\GopayPayment;

trait HasPayments
{
    protected $paymentMethods = [
        1 => GopayPayment::class
    ];

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

    public function onPaymentError()
    {
        $order = $this->getOrder();

        if ( is_callable($callback = $this->onPaymentErrorCallback) ) {
            return $callback($order);
        }
    }

    public function setPaymentMethods($methods)
    {
        $this->paymentMethods = $methods;
    }

    public function hasOnlinePayment()
    {
        return $this->getPaymentClass() ? true : false;
    }

    public function getPaymentClass($paymentMethodId = null)
    {
        $order = $this->getOrder();

        $paymentMethodId = $paymentMethodId ?: $order->payment_method_id;

        if ( array_key_exists($paymentMethodId, $this->paymentMethods) ) {
            $paymentClass = new $this->paymentMethods[$paymentMethodId];

            return $paymentClass->setOrder($this->getOrder())
                                ->setPaymentMethod($this->getOrder()->payment_method);
        }
    }

    /*
     * Create order payment
     */
    public function makePayment($paymentMethodId = null)
    {
        $order = $this->getOrder();

        return $order->payments()->create([
            'price' => $order->price_tax,
            'payment_method_id' => $paymentMethodId ?: $order->payment_method_id,
            'uniqid' => uniqid().str_random(10),
        ]);
    }

    public function getPaymentRedirect($paymentMethodId = null)
    {
        $payment = $this->makePayment();

        $paymentClass = $this->getPaymentClass($paymentMethodId);
        $paymentClass->setPayment($payment);

        return $paymentClass->getPaymentUrl();
    }
}
?>