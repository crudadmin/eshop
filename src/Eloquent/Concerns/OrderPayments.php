<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Mail\OrderPaid;
use Exception;
use Illuminate\Support\Facades\Mail;
use Log;
use OrderService;

trait OrderPayments
{
    public function getPaymentData($paymentMethodId = null)
    {
        $paymentMethodId = $paymentMethodId ?: $this->payment_method_id;

        $this->bootOrderIntoOrderService();

        if ( !($paymentClass = OrderService::bootPaymentProvider($paymentMethodId)) ){
            return [];
        }

        return array_merge(
            [
                'provider' => class_basename(get_class($this->getPaymentProvider($paymentMethodId)))
            ],
            $paymentClass->getPaymentData(
                $paymentClass->getResponse()
            )
        );
    }

    public function getPaymentDataAttribute()
    {
        return $this->getPaymentData();
    }

    public function getPostPaymentUrlAttribute()
    {
        return $this->getPostPaymentUrl();
    }

    public function getPaymentProvider($paymentMethodId = null)
    {
        return OrderService::getPaymentProvider($paymentMethodId);
    }

    public function getPaymentUrl($paymentMethodId = null)
    {
        $paymentMethodId = $paymentMethodId ?: $this->payment_method_id;

        if ( !($paymentClass = OrderService::bootPaymentProvider($paymentMethodId)) ){
            return [];
        }

        return $paymentClass->getPaymentUrl(
            $paymentClass->getResponse()
        );
    }

    public function getPostPaymentUrl($paymentMethodId = null)
    {
        $paymentMethodId = $paymentMethodId ?: $this->payment_method_id;

        $this->bootOrderIntoOrderService();

        if ( !($paymentClass = OrderService::getPaymentProvider($paymentMethodId)) ){
            return;
        }

        return $paymentClass->getPostPaymentUrl(
            $paymentClass->getResponse()
        );
    }

    public function sendPaymentEmail($type = 'invoice', $invoice = null)
    {
        try {
            //Generate invoice
            $invoice = $invoice ?: $this->makeInvoice($type);

            Mail::to($this->email)->send(
                new OrderPaid($this, $invoice)
            );

            $invoice->setNotified();
        } catch (Exception $e){
            Log::channel('store')->error($e);

            $this->log()->create([
                'type' => 'error',
                'code' => 'email-payment-done-error',
            ]);
        }
    }
}

?>