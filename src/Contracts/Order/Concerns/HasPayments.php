<?php

namespace AdminEshop\Contracts\Order\Concerns;

use Admin;
use AdminEshop\Contracts\Payments\Concerns\PaymentErrorCodes;
use AdminEshop\Contracts\Payments\GopayPayment;
use AdminEshop\Events\OrderPaid as OrderPaidEvent;
use AdminEshop\Mail\OrderPaid;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Mail;
use Log;

trait HasPayments
{
    protected $paymentTypesConfigKey = 'admineshop.payment_methods.providers';

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
        return $this->getPaymentProvider($paymentMethodId) ? true : false;
    }

    public function getPaymentProvider($paymentMethodId = null)
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

    public function bootPaymentProvider($paymentMethodId)
    {
        if ( !$this->hasOnlinePayment($paymentMethodId) ){
            return false;
        }

        return Admin::cache('payments.'.$paymentMethodId.'.data', function() use ($paymentMethodId) {
            try {
                $payment = $this->makePayment($paymentMethodId);

                $paymentClass = $this->getPaymentProvider($paymentMethodId);

                $paymentClass->setPayment($payment);

                $paymentClass->setResponse(
                    $paymentClass->getPaymentResponse()
                );

                return $paymentClass;
            } catch (Exception $e){
                $this->logPaymentError($e);

                if ( $this->isDebug() ) {
                    throw $e;
                }
            }
        });
    }

    public function orderPaid()
    {
        $order = $this->order;

        //If order is paid already
        if ( $order->paid_at ) {
            return;
        }

        event(new OrderPaidEvent($order));

        //Update order status paid
        $order->update([ 'paid_at' => Carbon::now() ]);

        //Countdown product stock on payment
        if ( config('admineshop.stock.countdown.on_order_paid', true) == true ) {
            $order->syncStock('-', 'order.paid');
        }

        //Send invoice email
        if ( config('admineshop.mail.order.paid_notification', true) == true ) {
            //Generate invoice
            $invoice = $this->makeInvoice('invoice');

            try {
                Mail::to($order->email)->send(
                    new OrderPaid($order, $invoice)
                );
            } catch (Exception $e){
                Log::channel('store')->error($e);

                $order->log()->create([
                    'type' => 'error',
                    'code' => 'email-payment-done-error',
                ]);
            }
        }
    }
}
?>