<?php

namespace AdminEshop\Controllers\Cart;

use Admin;
use AdminEshop\Contracts\Cart\CartItemIdentifier;
use AdminEshop\Contracts\Discounts\DiscountCode;
use AdminEshop\Controllers\Controller;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Store\PaymentsMethod;
use Cart;
use Facades\AdminEshop\Contracts\Order\Mutators\DeliveryMutator;
use Facades\AdminEshop\Contracts\Order\Mutators\PaymentMethodMutator;

class CartController extends Controller
{
    /*
     * Verify if row exists in db and return row key
     */
    private function getProductId()
    {
        return Admin::cache('cart.product_id', function(){
            return Admin::getModelByTable('products')
                        ->select(['id'])
                        ->where('id', request('product_id'))
                        ->firstOrFail()
                        ->getKey();
        });
    }

    /*
     * Verify if variant exists in db and returns key
     */
    private function getVariantId()
    {
        if ( ! request('variant_id') ) {
            return;
        }

        return Admin::cache('cart.variant_id', function(){
            return Admin::getModelByTable('products_variants')
                        ->select(['id'])
                        ->where('id', request('variant_id'))
                        ->where('product_id', $this->getProductId())
                        ->firstOrFail()
                        ->getKey();
        });
    }

    public function addItem()
    {
        $identifier = new CartItemIdentifier($this->getProductId(), $this->getVariantId());

        Cart::addOrUpdate($identifier, request('quantity'));

        return Cart::response();
    }

    public function updateQuantity()
    {
        $identifier = new CartItemIdentifier($this->getProductId(), $this->getVariantId());

        Cart::updateQuantity($identifier, request('quantity'));

        return Cart::response();
    }

    public function removeItem()
    {
        $identifier = new CartItemIdentifier($this->getProductId(), $this->getVariantId());

        Cart::remove($identifier);

        return Cart::response();
    }

    public function addDiscountCode()
    {
        $code = request('code');

        validator()->make(request()->all(), ['code' => 'required'])->validate();

        if ( !($code = DiscountCode::getDiscountCode($code)) ) {
            autoAjax()->throwValidation([
                'code' => _('Zadaný kod nie je platný'),
            ]);
        }

        DiscountCode::saveDiscountCode($code->code);

        return Cart::response();
    }

    public function removeDiscountCode()
    {
        DiscountCode::removeDiscountCode();

        return Cart::response();
    }

    public function setDelivery()
    {
        $deliveryId = request('delivery_id');

        $delivery = Delivery::findOrFail($deliveryId);

        DeliveryMutator::saveDelivery($delivery->getKey());

        //If no payment method is present, reset payment method to null
        //Because payment method may be selected, but will be unavailable
        //under this selected delivery
        if ( ! PaymentMethodMutator::getSelectedPaymentMethod() ) {
            PaymentMethodMutator::savePaymentMethod(null);
        }

        return Cart::fullCartResponse();
    }

    public function setPaymentMethod()
    {
        $paymentMethodId = request('payment_method_id');

        $paymentMethod = PaymentsMethod::findOrFail($paymentMethodId);

        PaymentMethodMutator::savePaymentMethod($paymentMethod->getKey());

        return Cart::fullCartResponse();
    }
}
