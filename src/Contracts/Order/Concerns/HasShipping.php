<?php

namespace AdminEshop\Contracts\Order\Concerns;

use AdminEshop\Contracts\Delivery\Jobs\SendShippingJob;

trait HasShipping
{
    protected $configKey = 'admineshop.delivery.providers';

    public function getShippingProvider($deliveryId = null)
    {
        $order = $this->getOrder();

        $deliveryId = $deliveryId ?: $order->delivery_id;

        return $this->getProviderById($this->configKey, $deliveryId);
    }

    /*
     * Create order payment
     */
    public function sendShipping()
    {
        SendShippingJob::dispatch($this->getOrder());
    }
}