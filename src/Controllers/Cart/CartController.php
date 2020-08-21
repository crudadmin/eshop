<?php

namespace AdminEshop\Controllers\Cart;

use Admin;
use AdminEshop\Contracts\Cart\Identifiers\ProductsIdentifier;
use AdminEshop\Contracts\Discounts\DiscountCode;
use AdminEshop\Contracts\Order\Mutators\CountryMutator;
use AdminEshop\Controllers\Controller;
use AdminEshop\Events\DiscountCodeAdded;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Store\PaymentsMethod;
use Cart;
use Facades\AdminEshop\Contracts\Order\Mutators\DeliveryMutator;
use Facades\AdminEshop\Contracts\Order\Mutators\PaymentMethodMutator;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    /**
     * Basic cart summary with products data...
     *
     * @return  arrat
     */
    public function getSummary()
    {
        return Cart::defaultResponse();
    }

    /**
     * Full cart summary with delivery data, client data etc...
     *
     * @return  arrat
     */
    public function getFullSummary()
    {
        return Cart::fullCartResponse();
    }

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
        $identifier = new ProductsIdentifier($this->getProductId(), $this->getVariantId());

        Cart::addOrUpdate($identifier, request('quantity'));

        return Cart::defaultResponse();
    }

    public function updateQuantity()
    {
        $identifier = new ProductsIdentifier($this->getProductId(), $this->getVariantId());

        Cart::updateQuantity($identifier, request('quantity'));

        return Cart::defaultResponse();
    }

    public function removeItem()
    {
        $identifier = new ProductsIdentifier($this->getProductId(), $this->getVariantId());

        Cart::remove($identifier);

        return Cart::defaultResponse();
    }

    public function addDiscountCode()
    {
        $code = request('code');

        validator()->make(request()->all(), ['code' => 'required'])->validate();

        if ( !($code = DiscountCode::getDiscountCode($code)) ) {
            throw ValidationException::withMessages([
                'code' => _('Zadaný kód nie je platný.'),
            ]);
        }

        DiscountCode::saveDiscountCode($code->code);

        //Event for added discount code
        event(new DiscountCodeAdded($code));

        return Cart::defaultResponse();
    }

    public function removeDiscountCode()
    {
        DiscountCode::removeDiscountCode();

        return Cart::defaultResponse();
    }

    public function setDelivery()
    {
        $deliveryId = request('delivery_id');
        $locationId = request('location_id');

        //Find by delivery id
        if ( $deliveryId ) {
            $delivery = Delivery::findOrFail($deliveryId);

            //Find by location under given delivery
            if ( $locationId ) {
                $location = $delivery->locations()->findOrFail($locationId);
            }
        }

        DeliveryMutator::saveDelivery(
            isset($delivery) ? $delivery->getKey() : null,
            isset($location) ? $location->getKey() : null
        );

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

    public function setCountry()
    {
        $countryId = request('country_id');

        (new CountryMutator)->setCountry($countryId);

        return Cart::fullCartResponse();
    }
}
