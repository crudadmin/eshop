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
        return $order->delivery_id && $order->delivery ? $order->delivery : null;
    }

    /**
     * Add delivery field into order row
     *
     * @param  array  $row
     * @return array
     */
    public function mutateOrder(Order $order, $delivery)
    {
        $order->fill([
            'delivery_tax' => Store::getTaxValueById($delivery->tax_id),
            'delivery_price' => $delivery->priceWithoutTax,
            'delivery_id' => $delivery->getKey(),
        ]);
    }

    /**
     * Mutate sum price of order/cart
     *
     * @param  AdminEshop\Models\Delivery\Delivery|null  $delivery
     * @param  float  $price
     * @param  bool  $withTax
     * @return  void
     */
    public function mutatePrice($delivery, $price, bool $withTax)
    {
        //Add delivery price
        $price += $delivery->{$withTax ? 'priceWithTax' : 'priceWithoutTax'};

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