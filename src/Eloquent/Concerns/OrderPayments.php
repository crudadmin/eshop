<?php

namespace AdminEshop\Eloquent\Concerns;

use OrderService;

trait OrderPayments
{
    private function bootOrderIntoOrderService()
    {
        $order = OrderService::getOrder();

        //If order in payment helper is not set already
        if ( !$order || $order->getKey() != $this->getKey() ){
            OrderService::setOrder($this);
        }
    }

    public function getPaymentData($paymentMethodId = null)
    {
        $paymentMethodId = $paymentMethodId ?: $this->payment_method_id;

        $this->bootOrderIntoOrderService();

        if ( !($paymentClass = OrderService::bootPaymentClass($paymentMethodId)) ){
            return [];
        }

        return array_merge(
            [
                'provider' => class_basename(get_class(OrderService::getPaymentClass()))
            ],
            $paymentClass->getPaymentData(
                $paymentClass->getResponse()
            )
        );
    }

    public function getPaymentUrl($paymentMethodId = null)
    {
        $paymentMethodId = $paymentMethodId ?: $this->payment_method_id;

        if ( !($paymentClass = OrderService::bootPaymentClass($paymentMethodId)) ){
            return [];
        }

        return $paymentClass->getPaymentUrl(
            $paymentClass->getResponse()
        );
    }

    public function getPostPaymentUrl($paymentMethodId = null)
    {
        $paymentMethodId = $paymentMethodId ?: $this->payment_method_id;

        if ( !($paymentClass = OrderService::bootPaymentClass($paymentMethodId)) ){
            return;
        }

        return $paymentClass->getPostPaymentUrl(
            $paymentClass->getResponse()
        );
    }
}

?>