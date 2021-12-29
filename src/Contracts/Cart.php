<?php

namespace AdminEshop\Contracts;

use Admin;
use AdminEshop\Contracts\Cart\Concerns\CartTrait;
use AdminEshop\Contracts\Cart\Concerns\DriverSupport;
use AdminEshop\Contracts\Cart\Concerns\HasStockBlockSupport;
use AdminEshop\Contracts\Cart\Identifiers\Identifier;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Eloquent\Concerns\CanBeInCart;
use AdminEshop\Events\CartUpdated;
use Admin\Core\Contracts\DataStore;
use CartDriver;
use Discounts;
use OrderService;
use Store;

class Cart
{
    use CartTrait,
        DataStore,
        DriverSupport,
        HasStockBlockSupport;

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

    /**
     * Cart constructor
     */
    public function __construct()
    {
        //We does not want fetch items from session in admin interface
        if ( Admin::isAdmin() == true ) {
            $this->items = new CartCollection;
        }

        //In every other environment than admin, we want fetch items from session
        else {
            $this->setItemsFromDriver(
                $this->getDriver()->get('items')
            );
        }
    }

    /**
     * Return booted cart items
     *
     * @return  CartCollection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Add product into cart and save it into session
     * @param Identifier     $identifier
     * @param int|integer $quantity
     * @param Identifier     $parentIdentifier
     */
    public function addOrUpdate(Identifier $identifier, int $quantity = 1, Identifier $parentIdentifier = null)
    {
        //Cannot add negative quantity
        if ( !is_numeric($quantity) || $quantity <= 0 ){
            $quantity = 1;
        }

        //If items does exists in cart
        if ( $item = $this->getItem($identifier, $parentIdentifier) ) {
            $this->updateQuantity($identifier, $item->quantity + $quantity, $parentIdentifier);

            $this->pushToAdded($item);

            return $this;
        }

        $this->addNewItem($identifier, $quantity, $parentIdentifier);

        return $this;
    }

    /**
     * Update quantity for existing item in cart
     *
     * @param  Identifier $identifier
     * @param  int  $quantity
     * @param  Identifier $parentIdentifier
     * @return  this
     */
    public function updateQuantity(Identifier $identifier, $quantity, Identifier $parentIdentifier = null)
    {
        if ( ! ($item = $this->getItem($identifier, $parentIdentifier)) ) {
            autoAjax()->message(_('Produkt nebol nájdeny v košíku.'))->code(422)->throw();
        }

        $newQuantity = $this->checkQuantity($quantity);

        $difference = $item->getQuantity()-$newQuantity;

        //Set new quantity for actual cart item
        $item->setQuantity($newQuantity);

        //If quantity in parent product has been changed,
        //we want change quantity for assigned child cart items in same quantity difference
        $assignedCartItems = $this->items->filter(function($childItem) use ($item) {
            return $item->isParentOwner($childItem);
        })->each(function($item) use ($difference) {
            $item->setQuantity(
                $this->checkQuantity($item->getQuantity() - $difference)
            );
        });

        $this->saveItems();

        $this->pushToUpdated($item);

        return $this;
    }

    /**
     * Remove item from cart
     *
     * @param  Identifier $identifier
     * @return  this
     */
    public function remove(Identifier $identifier, Identifier $parentIdentifier = null)
    {
        $this->items = $this->items->reject(function($cartItem) use ($identifier, $parentIdentifier) {
            return $identifier->hasThisItem($cartItem) && $cartItem->hasSameParentIdentifier($parentIdentifier);
        })->values();

        $this->saveItems();

        return $this;
    }


    /**
     * Add product into cart and save it into session
     * @param Identifier     $identifier
     * @param int|integer $quantity
     * @param Identifier     $parentIdentifier
     */
    public function toggleItem(Identifier $identifier, Identifier $parentIdentifier = null)
    {
        //If items does exists in cart
        if ( $item = $this->getItem($identifier, $parentIdentifier) ) {
            $this->remove($identifier, $parentIdentifier);

            return $this;
        }

        $this->addNewItem($identifier, 1, $parentIdentifier);

        return $this;
    }

    /**
     * Returns cart response
     *
     * @return  array
     */
    public function response($fullCartResponse = false)
    {
        $items = $this->allWithMutators();

        $response = [
            'cartToken' => $this->getDriver()->getToken(),
            'items' => $items,
            'itemsHidden' => $this->addItemsFromMutators((new CartCollection), 'addHiddenCartItems', null),
            'discounts' => array_map(function($discount){
                return $discount->toArray();
            }, Discounts::getDiscounts()),
            'addedItems' => $this->addedItems,
            'updatedItems' => $this->updatedItems,
            'summary' => $items->getSummary(),
            'summaryTotal' => $items->getSummary($fullCartResponse),
        ];

        //Mutate cart response
        foreach (OrderService::getMutators() as $mutator) {
            //Mutate basic response
            if ( method_exists($mutator, 'mutateCartResponse') ) {
                $response = $mutator->mutateCartResponse($response);
            }

            //Mutate response with all additional data
            if ( $fullCartResponse == true && method_exists($mutator, 'mutateFullCartResponse') ) {
                $response = $mutator->mutateFullCartResponse($response);
            }
        }

        return $response;
    }

    /**
     * Return default cart response by configuration
     *
     * @return  array
     */
    public function baseResponse()
    {
        return $this->response(config('admineshop.cart.default_full_response', false));
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
     * Returns item from cart session
     *
     * @param  AdminEshop\Contracts\Cart\Identifiers|Identifier|AdminEshop\Eloquent\Concerns\CanBeInCart  $identifier
     */
    public function getItem($identifier, $parentIdentifier = null)
    {
        //If we want cart item by given model. We need receive identifier from this model
        if ( $identifier instanceof CanBeInCart ) {
            $identifier = $identifier->getIdentifier();
        }

        //If we want cart item by given identifier
        if ( !($identifier instanceof Identifier) ) {
            abort(500, 'Unknown identifier type.');
        }

        //All identifiers must match
        $items = $this->items->filter(function($cartItem) use ($identifier, $parentIdentifier) {
            return $identifier->hasThisItem($cartItem) && $cartItem->hasSameParentIdentifier($parentIdentifier);
        });

        return $items->first();
    }

    /**
     * Add new item into cart
     *
     * @param  Identifier  $identifier
     * @param  int  $quantity
     * @param  Identifier  $parentIdentifier
     */
    private function addNewItem(Identifier $identifier, $quantity, Identifier $parentIdentifier = null)
    {
        $item = new CartItem($identifier, $quantity);

        if ( $parentIdentifier ) {
            $item->setParentIdentifier($parentIdentifier);
        }

        $this->items[] = $item;

        $this->saveItems();

        $this->pushToAdded($item);

        return $this;
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
                        $this->remove(
                            $item->getIdentifierClass(),
                            $item->getParentIdentifier()
                        );
                    });
    }

    /**
     * Return items with additional mutators items
     *
     * @param  null|array  $discounts
     * @param  string  $method
     *
     * @return  CartCollection
     */
    public function allWithMutators($discounts = null, $method = 'addCartItems')
    {
        $items = $this->all();

        return $this->addItemsFromMutators($items, $method, $discounts);
    }

    public function addItemsFromMutators(CartCollection $items, $method, $discounts = null)
    {
        foreach ( OrderService::getActiveMutators() as $mutator ) {
            if ( ! method_exists($mutator, $method) ) {
                continue;
            }

            $addItems = $mutator->{$method}($mutator->getActiveResponse())
                                ->toCartFormat($discounts);

            $items = $items->merge($addItems);
        }

        return $items;
    }

    /**
     * Forget all saved cart and order details
     *
     * @param  int|null  $id
     * @return  this
     */
    public function forget($force = false)
    {
        //On local environment does not flush data
        if ( ! app()->environment('local') || $force === true ) {
            //Remove all order mutators from session
            foreach (OrderService::getMutators() as $mutator) {
                if ( method_exists($mutator, 'onCartForget') ) {
                    $response = $mutator->onCartForget();
                }
            }

            CartDriver::flushAllExceptWhitespaced();

            //For reseting cart items
            event(new CartUpdated(new CartCollection));
        }

        return $this;
    }

    /**
     * We need reset cart items on driver flush
     */
    public function onDriverFlush()
    {
        $this->items = new CartCollection;
    }
}

?>