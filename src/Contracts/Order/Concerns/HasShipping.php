<?php

namespace AdminEshop\Contracts\Order\Concerns;

use AdminEshop\Contracts\Delivery\Jobs\SendShippingJob;
use AdminEshop\Models\Store\StoreExport;
use Admin\Helpers\File;

trait HasShipping
{
    protected $shippingConfigKey = 'admineshop.delivery.providers';

    public function getShippingProvider($deliveryId = null)
    {
        $order = $this->getOrder();

        $deliveryId = $deliveryId ?: $order->delivery_id;

        return $this->getProviderById($this->shippingConfigKey, $deliveryId);
    }

    public function getShippingProviders()
    {
        $order = $this->getOrder();
        $providers = [];

        foreach (config($this->shippingConfigKey) as $deliveryId => $config) {
            $provider = $this->getProviderById($this->shippingConfigKey, $deliveryId);
            $classname = get_class($provider);

            $providers[$classname] = $provider;
        }

        return $providers;
    }

    /*
     * Create order payment
     */
    public function sendShipping($options = [])
    {
        SendShippingJob::dispatch($this->getOrder(), $options);
    }
}