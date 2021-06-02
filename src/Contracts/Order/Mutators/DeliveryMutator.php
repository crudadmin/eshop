<?php

namespace AdminEshop\Contracts\Order\Mutators;

use Admin;
use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Contracts\Order\Validation\DeliveryLocationValidator;
use AdminEshop\Contracts\Order\Validation\DeliveryValidator;
use AdminEshop\Events\DeliverySelected;
use AdminEshop\Models\Orders\Order;
use Cart;
use Store;

class DeliveryMutator extends Mutator
{
    /**
     * Register validator with this mutators
     *
     * @var  array
     */
    protected $validators = [
        DeliveryValidator::class,
        DeliveryLocationValidator::class,
    ];

    /*
     * driver key for delivery
     */
    const DELIVERY_KEY = 'delivery';

    /*
     * driver location key
     */
    const DELIVERY_LOCATION_KEY = 'delivery_location';

    /**
     * Returns if mutators is active
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActive()
    {
        if ( $delivery = $this->getSelectedDelivery() ) {
            return [
                'delivery' => $this->getSelectedDelivery(),
                'delivery_location' => $this->getSelectedLocation(),
            ];
        }
    }

    /**
     * Returns if mutators is active in administration
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActiveInAdmin(Order $order)
    {
        $delivery = $order->delivery_id && $order->delivery
                        ? Cart::addCartDiscountsIntoModel($order->delivery)
                        : null;

        if ( $delivery ) {
            return [
                'delivery' => $delivery,
                'delivery_location' => $order->delivery_location_id && $order->delivery_location ? $order->delivery_location : null,
            ];
        }
    }

    /**
     * Add delivery field into order row
     *
     * @param  array  $row
     * @return array
     */
    public function mutateOrder(Order $order, $activeResponse)
    {
        //We can fill order with delivery only if is creating new order.
        //Or if manual delivery is turned off.
        if ( $this->canGenerateDelivery($order) ) {
            $delivery = $activeResponse['delivery'];
            $location = $activeResponse['delivery_location'] ?? null;

            $order->fill([
                'delivery_vat' => Store::getVatValueById($delivery->vat_id),
                'delivery_price' => $delivery->priceWithoutVat,
                'delivery_id' => $delivery->getKey(),
                'delivery_location_id' => $location ? $location->getKey() : null,
            ]);
        }
    }

    /**
     * Check if order is in state where we can generate delivery prices automatically...
     *
     * @param  Order  $order
     * @return  bool
     */
    private function canGenerateDelivery(Order $order)
    {
        return $order->exists == false || $order->delivery_manual == false;
    }

    /**
     * Mutate sum price of order/cart
     *
     * @param  array $activeResponse
     * @param  float  $price
     * @param  bool  $withVat
     * @param  Order  $order
     * @return  void
     */
    public function mutatePrice($activeResponse, $price, bool $withVat, Order $order)
    {
        //Add delivery price automatically
        if ( $this->canGenerateDelivery($order) ) {
            $delivery = $activeResponse['delivery'];

            $price += $delivery->{$withVat ? 'priceWithVat' : 'priceWithoutVat'};
        }

        //Add manually typed delivery price into order price sum
        else {
            if ( $withVat ) {
                $price += Store::addVat($order->delivery_price, $order->delivery_vat);
            } else {
                $price += $order->delivery_price;
            }
        }

        return $price;
    }

    /**
     * Mutation of cart response request
     *
     * @param  $response
     * @return  array
     */
    public function mutateFullCartResponse($response) : array
    {
        return array_merge($response, [
            'deliveries' => $this->getFilteredDeliveriesWithDiscounts(),
            'selectedDelivery' => $this->getSelectedDelivery(),
            'selectedLocation' => $this->getSelectedLocation(),
        ]);
    }

    private function getFilteredDeliveriesWithDiscounts()
    {
        $deliveries = $this->getDeliveries();

        //If countries filter support is enabled,
        //and country has been selected
        if (
            config('admineshop.delivery.countries') == true
            && $selectedCountry = $this->getCountryMutator()->getSelectedCountry()
        ) {
            $deliveries = $deliveries->filter(function($delivery) use ($selectedCountry) {
                $allowedCountries = $delivery->countries->pluck('id')->toArray();

                //No countries has been specified, allowed is all
                if ( count($allowedCountries) == 0 ){
                    return true;
                }

                return in_array($selectedCountry->getKey(), $allowedCountries);
            });
        }

        //If is price limiter available
        if ( config('admineshop.delivery.price_limit') ) {
            $priceWithVat = Cart::all()->getSummary()['priceWithVat'] ?? 0;

            $deliveries = $deliveries->filter(function($delivery) use ($priceWithVat) {
                if ( !$delivery->price_limit ){
                    return true;
                }

                return $priceWithVat <= $delivery->price_limit;
            });
        }

        return Cart::addCartDiscountsIntoModel(
            $deliveries->values()->each->setCartResponse()
        );
    }

    /*
     * Return cached all deliveries
     */
    public function getDeliveries()
    {
        return $this->cache('deliveries', function(){
            $with = [];

            if (
                config('admineshop.delivery.multiple_locations') == true
                && config('admineshop.delivery.multiple_locations_autoload', false) == true
            ) {
                $with[] = 'locations:id,delivery_id,name';
            }

            if ( config('admineshop.delivery.countries') == true ) {
                $with[] = 'countries';
            }

            if ( config('admineshop.delivery.payments') == true ) {
                $with[] = 'payments';
            }

            return Admin::getModel('Delivery')
                        ->onlyAvailable()
                        ->with($with)
                        ->get();
        });
    }

    /*
     * Get delivery from driver
     */
    public function getSelectedDelivery()
    {
        $id = $this->getDriver()->get(self::DELIVERY_KEY);

        return $this->cache('selectedDelivery.'.$id, function() use ($id) {
            if ( $delivery = $this->getDeliveries()->where('id', $id)->first() ) {
                return Cart::addCartDiscountsIntoModel(
                    $delivery->setCartResponse()
                );
            }
        });
    }

    /*
     * Get location under delivery from driver
     */
    public function getSelectedLocation()
    {
        $id = $this->getDriver()->get(self::DELIVERY_LOCATION_KEY);

        return $this->cache('selectedLocation.'.$id, function() use ($id) {
            if ( !($delivery = $this->getSelectedDelivery()) ) {
                return;
            }

            return $delivery->locations()->where('id', $id)->first();
        });
    }

    /**
     * Save delivery into driver
     *
     * @param  int|null  $id
     * @param  bool  $persist
     * @return  this
     */
    public function setDelivery($id = null, $persist = true)
    {
        $this->getDriver()->set(self::DELIVERY_KEY, $id, $persist);

        event(new DeliverySelected($this->getSelectedDelivery()));

        return $this;
    }

    /**
     * Save delivery location into driver
     *
     * @param  int|null  $id
     * @param  bool  $persist
     * @return  this
     */
    public function setDeliveryLocation($locationId, $persist = true)
    {
        $this->getDriver()->set(self::DELIVERY_LOCATION_KEY, $locationId, $persist);

        return $this;
    }
}

?>