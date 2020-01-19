<?php

namespace AdminEshop\Contracts\Order\Mutators;

use Admin;
use AdminEshop\Contracts\Order\Mutators\Mutator;
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
    ];

    /*
     * Session key for delivery
     */
    private $sessionKey = 'cart.delivery';

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
            $order->fill([
                'delivery_tax' => Store::getTaxValueById($delivery->tax_id),
                'delivery_price' => $delivery->priceWithoutTax,
                'delivery_id' => $delivery->getKey(),
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
        ]);
    }

    /*
     * Return cached all deliveries
     */
    public function getDeliveries()
    {
        return $this->cache('deliveries', function(){
            return Admin::getModel('Delivery')->get();
        });
    }

    /*
     * Save delivery into session
     */
    public function getSelectedDelivery()
    {
        $id = session()->get($this->sessionKey);

        return $this->cache('selectedDelivery'.$id, function() use ($id) {
            return Cart::addCartDiscountsIntoModel($this->getDeliveries()->where('id', $id)->first());
        });
    }

    /**
     * Save delivery into session
     *
     * @param  int|null  $id
     * @return  this
     */
    public function saveDelivery($id = null)
    {
        session()->put($this->sessionKey, $id);
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
    }
}

?>