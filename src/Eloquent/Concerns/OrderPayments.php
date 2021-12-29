<?php

namespace AdminEshop\Eloquent\Concerns;

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
}

?>