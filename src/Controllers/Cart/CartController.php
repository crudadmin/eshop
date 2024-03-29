<?php

namespace AdminEshop\Controllers\Cart;

use Admin;
use AdminEshop\Contracts\Cart\Identifiers\ProductsIdentifier;
use AdminEshop\Contracts\Order\Mutators\ClientDataMutator;
use AdminEshop\Controllers\Controller;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Store\PaymentsMethod;
use Carbon\Carbon;
use Cart;
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
    private function getParentIdentifierClass($request)
    {
        if ( !($cartItem = $request['cart_item'] ?? null) ){
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
        return api(
            Cart::baseResponse()
        );
    }

    /**
     * Full cart summary with delivery data, client data etc...
     *
     * @return  arrat
     */
    public function getFullSummary()
    {
        return api(
            Cart::fullCartResponse()
        );
    }

    public function addItem()
    {
        $request = request()->all();

        $identifier = $this->getIdentifierClass($request);

        $parentIdentifier = $this->getParentIdentifierClass($request);

        Cart::addOrUpdate(
            $identifier,
            request('quantity'),
            $parentIdentifier
        );

        return api(
            Cart::baseResponse()
        );
    }

    public function toggleItems()
    {
        $items = request('items', []);

        foreach ($items as $itemData) {
            $identifier = $this->getIdentifierClass($itemData);

            $parentIdentifier = $this->getParentIdentifierClass($itemData);

            Cart::toggleItem(
                $identifier,
                $parentIdentifier
            );
        }

        return api(
            Cart::baseResponse()
        );
    }

    public function updateQuantity()
    {
        $request = request()->all();

        $identifier = $this->getIdentifierClass($request);

        $parentIdentifier = $this->getParentIdentifierClass($request);

        Cart::updateQuantity(
            $identifier,
            request('quantity'),
            $parentIdentifier
        );

        return api(
            Cart::baseResponse()
        );
    }

    public function removeItem()
    {
        $request = request()->all();

        $identifier = $this->getIdentifierClass($request);

        $parentIdentifier = $this->getParentIdentifierClass($request);

        Cart::remove($identifier, $parentIdentifier);

        return api(
            Cart::baseResponse()
        );
    }

    public function getDeliveryLocations($id)
    {
        $delivery = Admin::getModel('Delivery')->findOrFail($id);

        return api([
            'locations' => OrderService::getDeliveryMutator()->getLocationsByDelivery($delivery)->get(),
        ]);
    }

    public function setDelivery()
    {
        $deliveryId = request('delivery_id');
        $locationId = request('location_id');
        $data = request('data');

        //Find by delivery id
        if ( $deliveryId ) {
            $delivery = Admin::getModel('Delivery')->findOrFail($deliveryId);

            //Find by location under given delivery
            if ( $locationId ) {
                $location = OrderService::getDeliveryMutator()->getLocationByDelivery($locationId, $delivery);
            }
        }

        OrderService::getDeliveryMutator()->setDelivery(
            isset($delivery) ? $delivery->getKey() : null,
        );

        OrderService::getDeliveryMutator()->setDeliveryLocation(
            isset($location) && $location ? $location->getKey() : null,
        );

        if ( request()->has('data') ){
            OrderService::getDeliveryMutator()->setDeliveryData($data);
        }

        //If no payment method is unavailable for this delivery, reset payment method to null
        if ( ! OrderService::getPaymentMethodMutator()->getSelectedPaymentMethod() ) {
            OrderService::getPaymentMethodMutator()->setPaymentMethod(null);
        }

        return api(
            Cart::fullCartResponse()
        );
    }

    public function setDeliveryLocation()
    {
        $delivery = OrderService::getDeliveryMutator()->getSelectedDelivery();

        $location = OrderService::getDeliveryMutator()->getLocationByDelivery(
            request('id')
        );

        OrderService::getDeliveryMutator()->setDeliveryLocation(
            $location ? $location->getKey() : null,
        );

        return api(
            Cart::fullCartResponse()
        );
    }

    public function setPaymentMethod()
    {
        $paymentMethodId = request('payment_method_id');

        $paymentMethod = PaymentsMethod::findOrFail($paymentMethodId);

        OrderService::getPaymentMethodMutator()->setPaymentMethod($paymentMethod->getKey());

        return api(
            Cart::fullCartResponse()
        );
    }

    public function setCountry()
    {
        $countryId = request('country_id');

        OrderService::getCountryMutator()->setCountry($countryId);

        return api(
            Cart::fullCartResponse()
        );
    }

    public function storeAddress()
    {
        if ( $errorResponse = OrderService::validateOrder([
            ClientDataMutator::class,
        ]) ){
            return $errorResponse;
        }

        return Cart::fullCartResponse();
    }

    public function checkAccountExistance()
    {
        $email = request('email');

        $model = Admin::getModel('Client');
        $client = $model->where('email', $email)->first();

        $data = method_exists($model, 'onAccountExistanceCheck')
                    ? (($client ?: $model)->onAccountExistanceCheck($client) ?: [])
                    : [];

        return api(array_merge([
            'exists' => $client ? true : false,
        ], $data));
    }

    public function submitOrder()
    {
        if ( $errorResponse = OrderService::processFinalOrderValidation() ){
            return $errorResponse;
        }

        //Create order
        OrderService::store();

        //Send email to client
        if ( config('admineshop.mail.order.created', true) == true ) {
            //Generate default invoice document
            if ( config('admineshop.mail.with_proform', true) == true ) {
                $proform = OrderService::getOrder()->makeInvoice('proform', [
                    'notified_at' => Carbon::now(),
                ]);
            } else {
                $proform = null;
            }

            OrderService::sentClientEmail($proform);
        }

        //Sent store email
        if ( config('admineshop.mail.order.store_copy', true) == true ) {
            OrderService::sentStoreEmail();
        }

        //Send shipping
        OrderService::sendShipping();

        //Set order creation event
        OrderService::fireCreatedEvent();

        //Forget whole cart
        Cart::forget();

        $order = OrderService::getOrder();

        return autoAjax()->success(_('Objednávka bola úspešne odoslaná.'))->data([
            'order' => $order->setOrderResponse(),
            'order_hash' => $order->getHash(),
            'payment' => ($paymentData = $order->getPaymentData()) ? $paymentData : [],
            'cart' => Cart::fullCartResponse(),
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
            'order' => $order->makeHidden(['items'])->setSuccessOrderResponse(),
            'items' => $order->items->map(function($item){
                return $item->setSuccessOrderResponse();
            }),
        ]);
    }

    public function passesValidation($stepname)
    {
        if ( Cart::passesCartValidation($stepname) === false ) {
            return OrderService::errorResponse();
        }

        return api(
            Cart::getCartStepResponse($stepname)
        );
    }
}
