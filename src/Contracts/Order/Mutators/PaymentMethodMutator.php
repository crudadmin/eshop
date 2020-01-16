<?php

namespace AdminEshop\Contracts\Order\Mutators;

use Admin;
use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Contracts\Order\Validation\PaymentMethodValidator;
use AdminEshop\Models\Orders\Order;
use Cart;
use Facades\AdminEshop\Contracts\Order\Mutators\DeliveryMutator;
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
     * Session key for payment method
     */
    private $sessionKey = 'cart.paymentMethod';

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

    /**
     * Mutation of cart response request
     *
     * @param  $response
     *
     * @return  array
     */
    public function mutateCartResponse($response) : array
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
            return Admin::getModel('PaymentsMethod')->get();
        });
    }

    /*
     * Save delivery into session
     */
    public function getSelectedPaymentMethod()
    {
        $id = session()->get($this->sessionKey);

        //We need to save also delivery key into cacheKey,
        //because if delivery would change, paymentMethod can dissapear
        //if is not assigned into selected delivery
        $delivery = DeliveryMutator::getSelectedDelivery();

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
        $delivery = DeliveryMutator::getSelectedDelivery();

        $allowedPaymentMethods = !$delivery ? [] : $delivery->payments()->pluck('payments_methods.id')->toArray();

        //If any rule is present, allow all payment methods
        if ( count($allowedPaymentMethods) == 0 ) {
            return $this->getPaymentMethods();
        }

        return $this->getPaymentMethods()->filter(function($item) use ($allowedPaymentMethods) {
            return in_array($item->getKey(), $allowedPaymentMethods);
        });
    }

    /**
     * Save payment method into session
     *
     * @param  int|null  $id
     * @return  this
     */
    public function savePaymentMethod($id = null)
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