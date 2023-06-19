<?php

namespace AdminEshop\Contracts\Order\Mutators;

use Admin;
use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Contracts\Order\Validation\DeliveryLocationValidator;
use AdminEshop\Contracts\Order\Validation\DeliveryPointValidator;
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
        DeliveryPointValidator::class,
    ];

    /*
     * driver key for delivery
     */
    const DELIVERY_KEY = 'delivery';

    /*
     * driver location key
     */
    const DELIVERY_LOCATION_KEY = 'delivery_location';

    /*
     * additional delivery data key
     */
    const DELIVERY_DATA_KEY = 'delivery_data';

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
                'delivery_data' => $this->getDeliveryData(),
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
        if ( !$order->delivery_id ) {
            return;
        }

        $order->load([
            'delivery' => function($query){
                $query->withCartResponse();
            }
        ]);

        if ( $order->delivery ) {
            return [
                'delivery' => $order->delivery,
                'delivery_location' => $order->delivery_location_id && $order->delivery_location ? $order->delivery_location : null,
                'delivery_data' => $order->delivery_data,
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
            $deliveryData = $activeResponse['delivery_data'] ?? null;
            $hasManualPrice = ($provider = $delivery->getShippingProvider()) && is_null($provider->getShippingPrice()) == false;

            $order->fill([
                'delivery_vat' => Store::getVatValueById($delivery->vat_id),
                'delivery_manual' => $hasManualPrice,
                'delivery_price' => $delivery->priceWithoutVat,
                'delivery_price_vat' => $delivery->priceWithVat,
                'delivery_id' => $delivery->getKey(),
                'delivery_location_id' => $location ? $location->getKey() : null,
                'delivery_data' => $deliveryData ? [ $delivery->getKey() => $deliveryData ] : null,
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
            'selectedDeliveryData' => $this->getDeliveryData(),
            'selectedLocation' => $this->getSelectedLocation(),
        ]);
    }

    private function getFilteredDeliveriesWithDiscounts()
    {
        $model = Admin::getModel('Delivery');

        $deliveries = $this->getDeliveries();

        $deliveries = $model->filterCartDeliveries($deliveries, $this);

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
            return Admin::getModel('Delivery')
                        ->onlyAvailable()
                        ->withCartResponse()
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
            return $this->getLocationByDelivery($id);
        });
    }

    /**
     * Returns available locations by selected delivery
     *
     * @param  Delivery  $delivery
     *
     * @return  Query|Model
     */
    public function getLocationsByDelivery($delivery = null)
    {
        if ( $this->hasDefaultDeliveryTable() ){
            return $delivery ? $delivery->locations() : null;
        } else {
            return Admin::getModelByTable(
                config('admineshop.delivery.multiple_locations.table')
            );
        }
    }

    /**
     * Returns location by delivery
     *
     * @param  int  $locationId
     * @param  Delivery|null  $delivery
     *
     * @return  AdminModel
     */
    public function getLocationByDelivery($locationId, $delivery = null)
    {
        $delivery = is_null($delivery) ? $this->getSelectedDelivery() : $delivery;

        if ( $locations = $this->getLocationsByDelivery($delivery) ){
            if ( $location = $locations->find($locationId) ){
                return $location->setCartResponse();
            }
        }
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

    /**
     * Save additional delivery data
     *
     * @param  [type]  $data
     * @param  bool  $persist
     */
    public function setDeliveryData($data, $persist = true)
    {
        $id = $this->getDriver()->get(self::DELIVERY_KEY);

        $this->getDriver()->set(self::DELIVERY_DATA_KEY.'.'.$id, $data, $persist);

        return $this;
    }

    /**
     * Returns delivery data
     *
     * @param  array  $data
     *
     * @return  mixed
     */
    public function getDeliveryData($deliveryId = null)
    {
        $id = $deliveryId ?: $this->getDriver()->get(self::DELIVERY_KEY);

        return $this->getDriver()->get(self::DELIVERY_DATA_KEY.'.'.$id);
    }

    public function hasDefaultDeliveryTable()
    {
        return config('admineshop.delivery.multiple_locations.table') == 'deliveries_locations';
    }
}

?>