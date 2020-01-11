<?php

namespace AdminEshop\Contracts;

use \Illuminate\Database\Eloquent\Collection;
use AdminEshop\Traits\BasketTrait;
use StoreDiscounts;
use Store;
use Admin;

class Basket
{
    use BasketTrait;

    /*
     * Items in basket
     */
    private $items = [];

    private $loadedProducts = [];

    private $loadedVariants = [];

    /*
     * Session key
     */
    private $key = 'basket.items';

    public function __construct()
    {
        $this->items = new Collection($this->fetchItemsFromSession());

        $this->loadedProducts = new Collection();

        $this->loadedVariants = new Collection();
    }

    /**
     * Add product into basket and save it into session
     * @param Product     $product
     * @param int|integer $quantity
     */
    public function addOrUpdate(int $productId, int $quantity = 1, $variantId = null)
    {

        //If items does not exists in basket
        if ( $item = $this->getItemFromBasket($productId, $variantId) ) {
            $this->updateQuantity($productId, $item->quantity + $quantity, $variantId);

            $this->pushToAdded($item);

            return $this;
        }

        $this->addNewItem($productId, $quantity, $variantId);


        return $this;
    }

    /**
     * Update quantity for existing item in basket
     *
     * @param  int  $productId
     * @param  int  $quantity
     * @param  int|null  $variantId
     * @return  this
     */
    public function updateQuantity(int $productId, $quantity, $variantId)
    {
        if ( ! ($item = $this->getItemFromBasket($productId, $variantId)) ) {
            abort(500, _('Produkt neexistuje v košíku.'));
        }

        $item->quantity = $this->checkQuantity($quantity);

        $this->save();

        $this->pushToUpdated($item);

        return $this;
    }

    /**
     * Remove item from basket
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
     * Returns basket response
     *
     * @return  array
     */
    public function response()
    {
        $items = $this->all();

        $discounts = StoreDiscounts::getDiscounts();

        return [
            'basket' => $items,
            'discounts' => $discounts,
            'addedItems' => $this->addedItems,
            'updatedItems' => $this->updatedItems,
            'summary' => $this->getSummary($items, $discounts),
        ];
    }

    /**
     * Returns item from basket
     *
     * @param  int  $productId
     * @param  int|null  $variantId
     * @return null|object
     */
    public function getItemFromBasket(int $productId, $variantId = null)
    {
        $items = $this->items->where('id', $productId);

        if ( $variantId ) {
            return $items->where('variant_id', $variantId)->first();
        }

        return $items->first();
    }

    /**
     * Add new item into basket
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
     * Save items from basket into session
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
     * Get all items from basket with loaded products and variants from db
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
            //If product or variant is missing from basket item, remove this basket item
            if ( ! $item->product || isset($item->variant_id) && ! $item->variant ) {
                $this->remove($item->id, @$item->variant_id ?: null);

                return true;
            }
        });
    }
}

?>