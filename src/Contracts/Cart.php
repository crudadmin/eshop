<?php

namespace AdminEshop\Contracts;

use Admin;
use AdminEshop\Contracts\Cart\Concerns\CartTrait;
use AdminEshop\Contracts\Cart\Identifiers\Identifier;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Order\Mutators\DeliveryMutator;
use AdminEshop\Contracts\Order\Mutators\PaymentMethodMutator;
use AdminEshop\Eloquent\Concerns\CanBeInCart;
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

    /**
     * Cart Driver
     * session/token
     *
     * @var  void
     */
    protected $driver;

    public function __construct()
    {
        $driver = config('admineshop.cart.driver');

        $this->driver = new $driver;

        //We does not want fetch items from session in admin interface
        if ( Admin::isAdmin() == true ) {
            $this->items = new CartCollection;
        }

        //In every other environment than admin, we want fetch items from session
        else {
            $this->items = $this->fetchItemsFromDriver();
        }
    }

    /**
     * Returns driver
     *
     * @return  AdminEshop\Contracts\Cart\Drivers\DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Add product into cart and save it into session
     * @param Product     $product
     * @param int|integer $quantity
     */
    public function addOrUpdate(Identifier $identifier, int $quantity = 1)
    {
        //If items does not exists in cart
        if ( $item = $this->getItem($identifier) ) {
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
        if ( ! ($item = $this->getItem($identifier)) ) {
            autoAjax()->message(_('Produkt nebol nájdeny v košíku.'))->code(422)->throw();
        }

        $item->setQuantity(
            $this->checkQuantity($quantity)
        );

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
    public function remove(Identifier $identifier)
    {
        $this->items = $this->items->reject(function($item) use ($identifier) {
            return $identifier->hasThisItem($item);
        })->values();

        $this->saveItems();

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
            foreach (OrderService::getMutators() as $mutator) {
                if ( method_exists($mutator, 'mutateCartResponse') ) {
                    $response = $mutator->mutateCartResponse($response);
                }
            }
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
     * Returns item from cart session
     *
     * @param  AdminEshop\Contracts\Cart\Identifiers|Identifier|AdminEshop\Eloquent\Concerns\CanBeInCart  $identifier
     */
    public function getItem($identifier)
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
        $items = $this->items->filter(function($item) use ($identifier) {
            return $identifier->hasThisItem($item);
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
                        $this->remove($item->getIdentifierClass());
                    });
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
            $this->driver->forgetItems();

            //Remove all order mutators from session
            foreach (OrderService::getMutators() as $mutator) {
                if ( method_exists($mutator, 'onCartForget') ) {
                    $response = $mutator->onCartForget();
                }
            }
        }

        session()->save();

        return $this;
    }
}

?>