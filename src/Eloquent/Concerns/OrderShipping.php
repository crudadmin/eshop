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

    public function getDeliveryPickupAddressAttribute()
    {
        if ( ($provider = $this->getShippingProvider()) && $name = $provider->getPickupAddress() ) {
            return $name;
        }

        if ( $location = $this->getPickupDeliveryLocation() ) {
            return $location?->address;
        }
    }

    public function getDeliveryPickupNameAttribute()
    {
        if ( ($provider = $this->getShippingProvider()) && $name = $provider->getPickupName() ) {
            return $name;
        }

        if ( $location = $this->getPickupDeliveryLocation() ) {
            return $location->{config('admineshop.delivery.multiple_locations.field_name')};
        }
    }

    /**
     * Helper pickup function
     * We can mutate delivery location with this function.
     *
     * @return  DeliveryLocation
     */
    public function getPickupDeliveryLocation()
    {
        return $this->delivery_location;
    }
}

?>