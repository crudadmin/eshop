<?php

namespace AdminEshop\Contracts\Order\Mutators;

use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Models\Orders\Order;
use Store;
use Cart;

class DeliveryMutator extends Mutator
{
    /**
     * Returns if mutators is active
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActive()
    {
        return Cart::getSelectedDelivery();
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
}

?>