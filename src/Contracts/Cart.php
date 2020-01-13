<?php

namespace AdminEshop\Contracts;

use Admin;
use AdminEshop\Contracts\Concerns\CartTrait;
use Discounts;
use Store;
use \Illuminate\Database\Eloquent\Collection;

class Cart
{
    use CartTrait;

    /*
     * Items in cart
     */
    private $items = [];

    private $loadedProducts = [];

    private $loadedVariants = [];

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
        $this->items = new Collection($this->fetchItemsFromSession());

        $this->loadedProducts = new Collection();

        $this->loadedVariants = new Collection();
    }

    /**
     * Add product into cart and save it into session
     * @param Product     $product
     * @param int|integer $quantity
     */
    public function addOrUpdate(int $productId, int $quantity = 1, $variantId = null)
    {

        //If items does not exists in cart
        if ( $item = $this->getItemFromCart($productId, $variantId) ) {
            $this->updateQuantity($productId, $item->quantity + $quantity, $variantId);

            $this->pushToAdded($item);

            return $this;
        }

        $this->addNewItem($productId, $quantity, $variantId);

        return $this;
    }

    /**
     * Update quantity for existing item in cart
     *
     * @param  int  $productId
     * @param  int  $quantity
     * @param  int|null  $variantId
     * @return  this
     */
    public function updateQuantity(int $productId, $quantity, $variantId)
    {
        if ( ! ($item = $this->getItemFromCart($productId, $variantId)) ) {
            abort(500, _('Produkt neexistuje v košíku.'));
        }

        $item->quantity = $this->checkQuantity($quantity);

        $this->save();

        $this->pushToUpdated($item);

        return $this;
    }

    /**
     * Remove item from cart
     *
     * @param  int  $productId
     * @param  int|null  $variantId
     * @return  this
     */
    public function remove(int $productId, $variantId = null)
    {
        $this->items = $this->items->reject(function($item) use ($productId, $variantId) {
            return $item->id == $productId && (
                $variantId ? $item->variant_id == $variantId : true
            );
        });

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

        $discounts = Discounts::getDiscounts();

        $response = [
            'items' => $items,
            'discounts' => array_map(function($discount){
                return $discount->toArray();
            }, $discounts),
            'addedItems' => $this->addedItems,
            'updatedItems' => $this->updatedItems,
            'summary' => $this->getSummary($items, $discounts),
        ];

        if ( $fullCartResponse == true ){
            $response = array_merge($response, [
                'deliveries' => $this->addCartDiscountsIntoModel(Store::getDeliveries()),
                'paymentMethods' => $this->addCartDiscountsIntoModel(Store::getPaymentMethodsByDelivery()),
                'selectedDelivery' => Cart::getSelectedDelivery(),
                'selectedPaymentMethod' => Cart::getSelectedPaymentMethod(),
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
     * @param  int  $productId
     * @param  int|null  $variantId
     * @return null|object
     */
    public function getItemFromCart(int $productId, $variantId = null)
    {
        $items = $this->items->where('id', $productId);

        if ( $variantId ) {
            return $items->where('variant_id', $variantId)->first();
        }

        return $items->first();
    }

    /**
     * Add new item into cart
     *
     * @param  int  $productId
     * @param  int  $quantity
     * @param  int|null  $variantId
     */
    private function addNewItem($productId, $quantity, $variantId)
    {
        $item = new \StdClass();
        $item->id = $productId;

        if ( $variantId ) {
            $item->variant_id = $variantId;
        }

        $item->quantity = $this->checkQuantity($quantity);

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
     * Get all items from cart with loaded products and variants from db
     *
     * @param  null|array  $discounts = null
     * @return  Collection
     */
    public function all($discounts = null)
    {
        $this->fetchMissingProductDataFromDb();

        return $this->items->map(function($item) use ($discounts) {
            return $this->mapProductData(clone $item, $discounts);
        })->reject(function($item){
            //If product or variant is missing from cart item, remove this cart item
            if ( ! $item->product || isset($item->variant_id) && ! $item->variant ) {
                $this->remove($item->id, @$item->variant_id ?: null);

                return true;
            }
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

    /*
     * Save delivery into session
     */
    public function getSelectedDelivery()
    {
        $id = session()->get($this->deliveryKey);

        return $this->addCartDiscountsIntoModel(Store::getDeliveries()->where('id', $id)->first());
    }

    /*
     * Save delivery into session
     */
    public function getSelectedPaymentMethod()
    {
        $id = session()->get($this->paymentMethodKey);

        return $this->addCartDiscountsIntoModel(Store::getPaymentMethodsByDelivery()->where('id', $id)->first());
    }
}

?>