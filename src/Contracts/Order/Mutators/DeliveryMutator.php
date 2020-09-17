<?php

namespace AdminEshop\Contracts\Order\Mutators;

use Admin;
use Facades\AdminEshop\Contracts\Order\Mutators\CountryMutator;
use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Contracts\Order\Validation\DeliveryLocationValidator;
use AdminEshop\Contracts\Order\Validation\DeliveryValidator;
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
    private $deliveryKey = 'delivery';

    /*
     * driver location key
     */
    private $deliveryLocationKey = 'delivery_location';

    /**
     * Returns if mutators is active
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActive()
    {
        return $this->getSelectedDelivery();
    }

    /**
     * Returns if mutators is active in administration
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActiveInAdmin(Order $order)
    {
        if ( $order->delivery_id && $order->delivery ) {
            return $order->delivery;
        }
    }

    /**
     * Add delivery field into order row
     *
     * @param  array  $row
     * @return array
     */
    public function mutateOrder(Order $order, $delivery)
    {
        //We can fill order with delivery only if is creating new order.
        //Or if manual delivery is turned off.
        if ( $this->canGenerateDelivery($order) ) {
            $location = $this->getSelectedLocation();

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
     * @param  AdminEshop\Models\Delivery\Delivery|null  $delivery
     * @param  float  $price
     * @param  bool  $withVat
     * @param  Order  $order
     * @return  void
     */
    public function mutatePrice($delivery, $price, bool $withVat, Order $order)
    {
        //Add delivery price automatically
        if ( $this->canGenerateDelivery($order) ) {
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
            && $selectedCountry = CountryMutator::getSelectedCountry()
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

        return Cart::addCartDiscountsIntoModel($deliveries);
    }

    /*
     * Return cached all deliveries
     */
    public function getDeliveries()
    {
        return $this->cache('deliveries', function(){
            $with = [];

            if ( config('admineshop.delivery.multiple_locations') == true ) {
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
        $id = $this->getDriver()->get($this->deliveryKey);

        return $this->cache('selectedDelivery'.$id, function() use ($id) {
            return Cart::addCartDiscountsIntoModel($this->getDeliveries()->where('id', $id)->first());
        });
    }

    /*
     * Get location under delivery from driver
     */
    public function getSelectedLocation()
    {
        $id = $this->getDriver()->get($this->deliveryLocationKey);

        return $this->cache('selectedLocation'.$id, function() use ($id) {
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
     * @param  int|null  $locationId
     * @return  this
     */
    public function saveDelivery($id = null, $locationId = null)
    {
        $this->getDriver()->set($this->deliveryKey, $id);
        $this->getDriver()->set($this->deliveryLocationKey, $locationId);

        return $this;
    }
}

?>