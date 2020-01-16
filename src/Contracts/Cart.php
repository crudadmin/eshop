<?php

namespace AdminEshop\Contracts;

use Admin;
use AdminEshop\Contracts\Cart\Concerns\CartTrait;
use AdminEshop\Contracts\Cart\Identifiers\HasIdentifier;
use AdminEshop\Contracts\Order\Mutators\DeliveryMutator;
use AdminEshop\Contracts\Order\Mutators\PaymentMethodMutator;
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

    public function __construct()
    {
        $this->items = $this->fetchItemsFromSession();
    }

    /**
     * Add product into cart and save it into session
     * @param Product     $product
     * @param int|integer $quantity
     */
    public function addOrUpdate(HasIdentifier $identifier, int $quantity = 1)
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
     * @param  HasIdentifier $identifier
     * @param  int  $quantity
     * @return  this
     */
    public function updateQuantity(HasIdentifier $identifier, $quantity)
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
     * @param  HasIdentifier $identifier
     * @return  this
     */
    public function remove(HasIdentifier $identifier)
    {
        $this->items = $this->items->reject(function($item) use ($identifier) {
            return $identifier->hasThisItem($item);
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
     * Returns item from cart
     *
     * @param  HasIdentifier  $identifier
     * @return null|object
     */
    public function getItemFromCart(HasIdentifier $identifier)
    {
        //All identifiers must match
        $items = $this->items->filter(function($item) use ($identifier) {
            return $identifier->hasThisItem($item);
        });

        return $items->first();
    }

    /**
     * Add new item into cart
     *
     * @param  HasIdentifier  $identifier
     * @param  int  $quantity
     */
    private function addNewItem(HasIdentifier $identifier, $quantity)
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