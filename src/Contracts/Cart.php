<?php

namespace AdminEshop\Contracts;

use Admin;
use AdminEshop\Contracts\Cart\Identifier;
use AdminEshop\Contracts\Concerns\CartTrait;
use Admin\Core\Contracts\DataStore;
use Discounts;
use OrderService;
use Store;

class Cart
{
    use CartTrait,
        DataStore;

    /*
     * Items in cart
     */
    private $items = [];

    /**
     * Returns response with all payments
     *
     * @var  bool
     */
    private $fullCartResponse = false;

    /*
     * Session key for basket items
     */
    private $key = 'cart.items';

    /*
     * Session key for delivery
     */
    private $deliveryKey = 'cart.delivery';

    /*
     * Session key for payment method
     */
    private $paymentMethodKey = 'cart.paymentMethod';

    public function __construct()
    {
        $this->items = $this->fetchItemsFromSession();
    }

    /**
     * Add product into cart and save it into session
     * @param Product     $product
     * @param int|integer $quantity
     */
    public function addOrUpdate(Identifier $identifier, int $quantity = 1)
    {
        //If items does not exists in cart
        if ( $item = $this->getItemFromCart($identifier) ) {
            $this->updateQuantity($identifier, $item->quantity + $quantity);

            $this->pushToAdded($item);

            return $this;
        }

        $this->addNewItem($identifier, $quantity);

        return $this;
    }

    /**
     * Update quantity for existing item in cart
     *
     * @param  Identifier $identifier
     * @param  int  $quantity
     * @return  this
     */
    public function updateQuantity(Identifier $identifier, $quantity)
    {
        if ( ! ($item = $this->getItemFromCart($identifier)) ) {
            autoAjax()->message(_('Produkt nebol nájdeny v košíku.'))->code(422)->throw();
        }

        $item->setQuantity($this->checkQuantity($quantity));

        $this->save();

        $this->pushToUpdated($item);

        return $this;
    }

    /**
     * Remove item from cart
     *
     * @param  Identifier $identifier
     * @return  this
     */
    public function remove(Identifier $identifier)
    {
        $this->items = $this->items->reject(function($item) use ($identifier) {
            return $identifier->isThisCartItem($item);
        })->values();

        $this->save();

        return $this;
    }

    /**
     * Returns cart response
     *
     * @return  array
     */
    public function response($fullCartResponse = false)
    {
        $items = $this->all();

        $response = [
            'items' => $items,
            'discounts' => array_map(function($discount){
                return $discount->toArray();
            }, Discounts::getDiscounts()),
            'addedItems' => $this->addedItems,
            'updatedItems' => $this->updatedItems,
            'summary' => $items->getSummary($fullCartResponse),
        ];

        if ( $fullCartResponse == true ){
            $response = array_merge($response, [
                'deliveries' => $this->addCartDiscountsIntoModel(Store::getDeliveries()),
                'paymentMethods' => $this->addCartDiscountsIntoModel(Store::getPaymentMethodsByDelivery()),
                'selectedDelivery' => Cart::getSelectedDelivery(),
                'selectedPaymentMethod' => Cart::getSelectedPaymentMethod(),
                'clientData' => OrderService::getFromSession(),
            ]);
        }

        return $response;
    }

    /**
     * Returns cart response with all additional payments
     *
     * @return  this
     */
    public function fullCartResponse()
    {
        return $this->response(true);
    }

    /**
     * Returns item from cart
     *
     * @param  CartIdentifier  $identifier
     * @return null|object
     */
    public function getItemFromCart(Identifier $identifier)
    {
        //All identifiers must match
        $items = $this->items->filter(function($item) use ($identifier) {
            return $identifier->isThisCartItem($item);
        });

        return $items->first();
    }

    /**
     * Add new item into cart
     *
     * @param  Identifier  $identifier
     * @param  int  $quantity
     */
    private function addNewItem(Identifier $identifier, $quantity)
    {
        $item = new CartItem($identifier, $quantity);

        $this->items[] = $item;

        $this->save();

        $this->pushToAdded($item);

        return $this;
    }

    /*
     * Save items from cart into session
     */
    public function save()
    {
        $items = ($this->items)->toArray();

        foreach ($items as $key => $item) {
            $items[$key] = (array)$items[$key];
        }

        session()->put($this->key, $items);
        session()->save();
    }

    /**
     * Get all items from cart with fetched products and variants from db
     *
     * @param  null|array  $discounts = null
     * @return  AdminEshop\Contracts\Collections\CartCollection
     */
    public function all($discounts = null)
    {
        return $this->items
                    ->toCartFormat($discounts, function($item){
                        $this->remove($item->getIdentifierClass());
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
        session()->put($this->deliveryKey, $id);
        session()->save();

        return $this;
    }

    /**
     * Save payment method into session
     *
     * @param  int|null  $id
     * @return  this
     */
    public function savePaymentMethod($id = null)
    {
        session()->put($this->paymentMethodKey, $id);
        session()->save();

        return $this;
    }

    /**
     * Forget all saved cart and order details
     *
     * @param  int|null  $id
     * @return  this
     */
    public function forget()
    {
        //On local environment does not flush data
        if ( ! app()->environment('local') ) {
            session()->forget($this->key);
            session()->forget($this->deliveryKey);
            session()->forget($this->paymentMethodKey);

            OrderService::flushFromSession();
        }

        session()->save();

        return $this;
    }

    /*
     * Save delivery into session
     */
    public function getSelectedDelivery()
    {
        $id = session()->get($this->deliveryKey);

        return $this->cache('selectedDelivery'.$id, function() use ($id) {
            return $this->addCartDiscountsIntoModel(Store::getDeliveries()->where('id', $id)->first());
        });
    }

    /*
     * Save delivery into session
     */
    public function getSelectedPaymentMethod()
    {
        $id = session()->get($this->paymentMethodKey);

        //We need to save also delivery key into cacheKey,
        //because if delivery would change, paymentMethod can dissapear
        //if is not assigned into selected delivery
        $delivery = $this->getSelectedDelivery();

        return $this->cache('selectedPaymentMethod'.$id.'-'.($delivery ? $delivery->getKey() : 0), function() use ($id) {
            return $this->addCartDiscountsIntoModel(Store::getPaymentMethodsByDelivery()->where('id', $id)->first());
        });
    }
}

?>