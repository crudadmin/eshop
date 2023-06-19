<?php

namespace AdminEshop\Eloquent\Concerns;

trait HasCartDeliveryFilter
{
    public function filterCartDeliveries($deliveries, $deliveryMutator)
    {
        //If countries filter support is enabled,
        //and country has been selected
        if (
            config('admineshop.delivery.countries') == true
            && $selectedCountry = $deliveryMutator->getCountryMutator()->getSelectedCountry()
        ) {
            $deliveries = $this->filterDeliveriesByCountries($deliveries, $selectedCountry);
        }

        //If is price limiter available
        if ( config('admineshop.delivery.price_limit') ) {
            $deliveries = $this->filterDeliveriesByPriceLimit($deliveries, $deliveryMutator->getCartItems());
        }

        $deliveries = $this->filterByMissingCalculatedPrices($deliveries);

        return $deliveries;
    }

    private function filterDeliveriesByCountries($deliveries, $selectedCountry)
    {
        return $deliveries->filter(function($delivery) use ($selectedCountry) {
            $allowedCountries = $delivery->countries->pluck('id')->toArray();

            //No countries has been specified, allowed is all
            if ( count($allowedCountries) == 0 ){
                return true;
            }

            return in_array($selectedCountry->getKey(), $allowedCountries);
        });
    }

    private function filterDeliveriesByPriceLimit($deliveries, $cartItems)
    {
        $priceWithVat = $cartItems->getSummary()['priceWithVat'] ?? 0;

        return $deliveries->filter(function($delivery) use ($priceWithVat) {
            if ( !$delivery->price_limit ){
                return true;
            }

            return $priceWithVat <= $delivery->price_limit;
        });
    }

    private function filterByMissingCalculatedPrices($deliveries)
    {
        return $deliveries->filter(function($delivery){
            if ( !($provider = $delivery->getShippingProvider()) ) {
                return true;
            }

            if ( !$provider->getOption('priceRequired') ){
                return true;
            }

            $price = $provider->getShippingPrice();

            if ( is_null($price) || $price === false ){
                return false;
            }

            return true;
        });
    }
}