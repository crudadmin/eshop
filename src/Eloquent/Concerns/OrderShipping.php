<?php

namespace AdminEshop\Eloquent\Concerns;

use OrderService;

trait OrderShipping
{
    public function getShippingProvider($deliveryId = null)
    {
        $this->bootOrderIntoOrderService();

        $deliveryId = $deliveryId ?: $this->delivery_id;

        return OrderService::getShippingProvider($deliveryId);
    }

    protected function getShippingButtons()
    {
        $buttons = [];

        $shippingProviders = OrderService::getShippingProviders();

        //Add buttons from shipping providers for exports
        foreach ($shippingProviders as $provider) {
            $buttons = array_merge($buttons, $provider->buttons());
        }

        return $buttons;
    }
}

?>