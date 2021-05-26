<?php

namespace AdminEshop\Contracts\Order\Mutators;

use Admin;
use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Contracts\Order\Validation\PaymentMethodValidator;
use AdminEshop\Events\PaymentSelected;
use AdminEshop\Models\Orders\Order;
use Cart;
use Store;

class PaymentMethodMutator extends Mutator
{
    /**
     * Register validator with this mutators
     *
     * @var  array
     */
    protected $validators = [
        PaymentMethodValidator::class,
    ];

    /*
     * driver key for payment method
     */
    const PAYMENT_METHOD_KEY = 'paymentMethod';

    /**
     * Returns if mutators is active
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActive()
    {
        return $this->getSelectedPaymentMethod();
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
            return Cart::addCartDiscountsIntoModel($order->paymentMethod);
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
        if ( $this->canGeneratePaymentMethod($order) ) {
            $order->fill([
                'payment_method_vat' => Store::getVatValueById($paymentMethod->vat_id),
                'payment_method_price' => $paymentMethod->priceWithoutVat,
                'payment_method_id' => $paymentMethod->getKey(),
            ]);
        }
    }

    /**
     * Check if order is in state where we can generate paymentMethod prices automatically...
     *
     * @param  Order  $order
     * @return  bool
     */
    private function canGeneratePaymentMethod(Order $order)
    {
        return $order->exists == false || $order->payment_method_manual == false;
    }

    /**
     * Mutate sum price of order/cart
     *
     * @param  AdminEshop\Models\Delivery\Delivery|null  $delivery
     * @param  float  $price
     * @param  bool  $withVat
     * @return  void
     */
    public function mutatePrice($paymentMethod, $price, bool $withVat, Order $order)
    {
        //Add payment method price
        if ( $this->canGeneratePaymentMethod($order) ) {
            $price += $paymentMethod->{$withVat ? 'priceWithVat' : 'priceWithoutVat'};
        } else {
            if ( $withVat ) {
                $price += Store::addVat($order->payment_method_price, $order->payment_method_vat);
            } else {
                $price += $order->payment_method_price;
            }
        }

        return $price;
    }

    /**
     * Mutation of cart response request
     *
     * @param  $response
     *
     * @return  array
     */
    public function mutateFullCartResponse($response) : array
    {
        return array_merge($response, [
            'paymentMethods' => Cart::addCartDiscountsIntoModel($this->getPaymentMethodsByDelivery()),
            'selectedPaymentMethod' => $this->getSelectedPaymentMethod(),
        ]);
    }

    /*
     * Return all payment methods
     */
    public function getPaymentMethods()
    {
        return $this->cache('paymentMethods', function(){
            return Admin::getModel('PaymentsMethod')->onlyAvailable()->get();
        });
    }

    /*
     * Save delivery into driver
     */
    public function getSelectedPaymentMethod()
    {
        $id = $this->getDriver()->get(self::PAYMENT_METHOD_KEY);

        //We need to save also delivery key into cacheKey,
        //because if delivery would change, paymentMethod can dissapear
        //if is not assigned into selected delivery
        $delivery = $this->getDeliveryMutator()->getSelectedDelivery();

        return $this->cache('selectedPaymentMethod'.$id.'-'.($delivery ? $delivery->getKey() : 0), function() use ($id) {
            return Cart::addCartDiscountsIntoModel($this->getPaymentMethodsByDelivery()->where('id', $id)->first());
        });
    }

    /**
     *  Return payment methods for selected delivery
     *
     * @return  array
     */
    public function getPaymentMethodsByDelivery()
    {
        $delivery = $this->getDeliveryMutator()->getSelectedDelivery();

        //If delivery is selected and payments rules are enabled, we can receive filter
        $allowedPaymentMethods = $delivery && config('admineshop.delivery.payments') == true
                                        ? $delivery->payments->pluck('id')->toArray()
                                        : [];

        //If any rule is present, allow all payment methods
        if ( count($allowedPaymentMethods) == 0 ) {
            return $this->getPaymentMethods();
        }

        return $this->getPaymentMethods()->filter(function($item) use ($allowedPaymentMethods) {
            return in_array($item->getKey(), $allowedPaymentMethods);
        })->values();
    }

    /**
     * Save payment method into driver
     *
     * @param  int|null  $id
     * @param  int|null  $persist
     * @return  this
     */
    public function setPaymentMethod($id = null, $persist = true)
    {
        $this->getDriver()->set(self::PAYMENT_METHOD_KEY, $id, $persist);

        event(new PaymentSelected($this->getSelectedPaymentMethod()));

        return $this;
    }
}

?>