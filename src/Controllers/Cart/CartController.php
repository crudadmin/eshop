<?php

namespace AdminEshop\Controllers\Cart;

use Admin;
use AdminEshop\Contracts\Cart\Identifiers\ProductsIdentifier;
use AdminEshop\Contracts\Discounts\DiscountCode;
use AdminEshop\Contracts\Order\Mutators\CountryMutator;
use AdminEshop\Controllers\Controller;
use AdminEshop\Events\DiscountCodeAdded;
use AdminEshop\Events\OrderCreated;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Store\PaymentsMethod;
use Cart;
use Facades\AdminEshop\Contracts\Order\Mutators\DeliveryMutator;
use Facades\AdminEshop\Contracts\Order\Mutators\PaymentMethodMutator;
use Illuminate\Validation\ValidationException;
use OrderService;

class CartController extends Controller
{
    /**
     * Returns booted ProductsIdentifier
     *
     * @param  int  $productId
     * @param  int  $variantId
     *
     * @return  AdminEshop\Contracts\Cart\Identifiers\ProductsIdentifier
     */
    private function getIdentifierClass(array $request = null)
    {
        if ( $identifierName = $request['identifier'] ?? null ) {
            $classname = Cart::getIdentifierByName($identifierName);
        } else {
            $classname = Cart::getIdentifierByClassName(config('admineshop.cart.default_identifier'));
        }

        $identifier = new $classname;
        $identifier->bootFromRequestData(
            is_array($request) ? $request : request()->all()
        );

        return $identifier;
    }

    /**
     * Returns parent identifier class
     *
     * @return  AdminEshop\Contracts\Cart\Identifiers\ProductsIdentifier
     */
    private function getParentIdentifierClass()
    {
        if ( !($cartItem = request('cart_item')) ){
            return;
        }

        return $this->getIdentifierClass($cartItem);
    }

    /**
     * Basic cart summary with products data...
     *
     * @return  arrat
     */
    public function getSummary()
    {
        return Cart::baseResponse();
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

    public function addItem()
    {
        $identifier = $this->getIdentifierClass();

        $parentIdentifier = $this->getParentIdentifierClass();

        Cart::addOrUpdate(
            $identifier,
            request('quantity'),
            $parentIdentifier
        );

        return Cart::baseResponse();
    }

    public function updateQuantity()
    {
        $identifier = $this->getIdentifierClass();

        $parentIdentifier = $this->getParentIdentifierClass();

        Cart::updateQuantity(
            $identifier,
            request('quantity'),
            $parentIdentifier
        );

        return Cart::baseResponse();
    }

    public function removeItem()
    {
        $identifier = $this->getIdentifierClass();

        $parentIdentifier = $this->getParentIdentifierClass();

        Cart::remove($identifier, $parentIdentifier);

        return Cart::baseResponse();
    }

    public function addDiscountCode()
    {
        $code = request('code');

        validator()->make(request()->all(), ['code' => 'required'])->validate();

        $code = DiscountCode::getDiscountCode($code);

        //Validate code and throw error
        if ( $errorMessage = (new DiscountCode)->getCodeError($code) ){
            throw ValidationException::withMessages([
                'code' => $errorMessage,
            ]);
        }

        DiscountCode::saveDiscountCode($code->code);

        //Event for added discount code
        event(new DiscountCodeAdded($code));

        return Cart::baseResponse();
    }

    public function getDeliveryLocations($id)
    {
        $delivery = Delivery::findOrFail($id);

        return api([
            'locations' => $delivery->locations,
        ]);
    }

    public function removeDiscountCode()
    {
        DiscountCode::removeDiscountCode();

        return Cart::baseResponse();
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

    public function submitOrder()
    {
        if ( $errorResponse = OrderService::validateOrder() ){
            return $errorResponse;
        }

        //Create order
        OrderService::store();

        //Add items into order
        OrderService::addItemsIntoOrder();

        //Send shipping
        OrderService::sendShipping();

        //Generate default invoice document
        $proform = OrderService::makeInvoice('proform');

        //Send email to client
        OrderService::sentClientEmail($proform);

        //Sent store email
        OrderService::sentStoreEmail();

        $order = OrderService::getOrder();

        //Event for added discount code
        event(new OrderCreated($order));

        //Forget whole cart
        Cart::forget();

        return autoAjax()->success(_('Objednávka bola úspešne odoslaná.'))->data([
            'order' => $order,
            'order_hash' => $order->getHash(),
            'payment' => ($paymentUrl = $order->getPaymentUrl()) ? [
                'url' => $paymentUrl,
                'provider' => class_basename(get_class(OrderService::getPaymentClass())),
            ] : [],
        ]);
    }

    public function success($orderId = null, $orderHash = null)
    {
        $order = Admin::getModelByTable('orders')
                    ->orderDetail()
                    ->orderCreated()
                    ->findOrFail($orderId ?: Cart::getDriver()->get('order_id'));

        //If hash of given order id in GET request is not correct. We cannot show items of given order
        if ( $orderId && $order->getHash() !== $orderHash ){
            abort(401);
        }

        return api([
            'order' => $order->makeHidden(['items'])->toResponseFormat(),
            'items' => $order->items->map(function($item){
                return $item->toResponseFormat();
            }),
        ]);
    }
}
