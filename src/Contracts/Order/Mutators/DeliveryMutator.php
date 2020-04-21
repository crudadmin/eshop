<?php

namespace AdminEshop\Contracts\Order\Mutators;

use Admin;
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
     * Session key for delivery
     */
    private $sessionKey = 'cart.delivery';

    /*
     * Session location key
     */
    private $sessionLocationKey = 'cart.delivery_location';

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
                'delivery_tax' => Store::getTaxValueById($delivery->tax_id),
                'delivery_price' => $delivery->priceWithoutTax,
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
     * @param  bool  $withTax
     * @param  Order  $order
     * @return  void
     */
    public function mutatePrice($delivery, $price, bool $withTax, Order $order)
    {
        //Add delivery price automatically
        if ( $this->canGenerateDelivery($order) ) {
            $price += $delivery->{$withTax ? 'priceWithTax' : 'priceWithoutTax'};
        }

        //Add manually typed delivery price into order price sum
        else {
            if ( $withTax ) {
                $price += Store::addTax($order->delivery_price, $order->delivery_tax);
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
    public function mutateCartResponse($response) : array
    {
        return array_merge($response, [
            'deliveries' => Cart::addCartDiscountsIntoModel($this->getDeliveries()),
            'selectedDelivery' => $this->getSelectedDelivery(),
            'selectedLocation' => $this->getSelectedLocation(),
        ]);
    }

    /*
     * Return cached all deliveries
     */
    public function getDeliveries()
    {
        return $this->cache('deliveries', function(){
            return Admin::getModel('Delivery')->with('locations')->get();
        });
    }

    /*
     * Get delivery from session
     */
    public function getSelectedDelivery()
    {
        $id = session()->get($this->sessionKey);

        return $this->cache('selectedDelivery'.$id, function() use ($id) {
            return Cart::addCartDiscountsIntoModel($this->getDeliveries()->where('id', $id)->first());
        });
    }

    /*
     * Get location under delivery from session
     */
    public function getSelectedLocation()
    {
        $id = session()->get($this->sessionLocationKey);

        return $this->cache('selectedLocation'.$id, function() use ($id) {
            if ( !($delivery = $this->getSelectedDelivery()) ) {
                return;
            }

            return $delivery->locations()->where('id', $id)->first();
        });
    }

    /**
     * Save delivery into session
     *
     * @param  int|null  $id
     * @param  int|null  $locationId
     * @return  this
     */
    public function saveDelivery($id = null, $locationId = null)
    {
        session()->put($this->sessionKey, $id);
        session()->put($this->sessionLocationKey, $locationId);
        session()->save();

        return $this;
    }

    /**
     * When cart is being forget state, we can flush session here
     * for this mutator.
     *
     * @return  void
     */
    public function onCartForget()
    {
        session()->forget($this->sessionKey);
        session()->forget($this->sessionLocationKey);
    }
}

?>