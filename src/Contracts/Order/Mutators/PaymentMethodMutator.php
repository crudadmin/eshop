<?php

namespace AdminEshop\Contracts\Order\Mutators;

use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Models\Orders\Order;
use Store;
use Cart;

class PaymentMethodMutator extends Mutator
{
    /**
     * Returns if mutators is active
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActive()
    {
        return Cart::getSelectedPaymentMethod();
    }

    /**
     * Returns if mutators is active in administration
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActiveInAdmin(Order $order)
    {
        if ( $order->payment_method_id && $order->paymentMethod ) {
            return $order->paymentMethod;
        }
    }

    /**
     * Add delivery field into order row
     *
     * @param  array  $row
     * @return array
     */
    public function mutateOrder(Order $order, $paymentMethod)
    {
        $order->fill([
            'payment_method_tax' => Store::getTaxValueById($paymentMethod->tax_id),
            'payment_method_price' => $paymentMethod->priceWithoutTax,
            'payment_method_id' => $paymentMethod->getKey(),
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
    public function mutatePrice($paymentMethod, $price, bool $withTax)
    {
        //Add payment method price
        $price += $paymentMethod->{$withTax ? 'priceWithTax' : 'priceWithoutTax'};

        return $price;
    }
}

?>