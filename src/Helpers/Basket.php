<?php

namespace AdminEshop\Helpers;

use \Illuminate\Database\Eloquent\Collection;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Orders\OrdersProduct;
use AdminEshop\Models\Store\Country;
use DB;
use Store;
use Admin;

class Basket
{
    /*
     * Items in basket
     */
    private $items = [];

    private $loadedProducts = [];

    private $loadedVariants = [];

    /*
     * Session key
     */
    private $key = 'basket_items';

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
    public function add(int $productId, int $quantity = 1, $variantId = null)
    {
        //If items does not exists in basket
        if ( $item = $this->getItemFromBasket($productId, $variantId) )
        {
            $this->updateQuantity($productId, $item->quantity + $quantity, $variantId);
        } else {
            $this->addNewItem($productId, $quantity, $variantId);
        }

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

    /*
     * Fetch items from session
     */
    private function fetchItemsFromSession()
    {
        $items = session($this->key, []);

        if ( ! is_array($items) )
            return [];

        return array_map(function($item){
            return (object)$item;
        }, $items);
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
    }

    /**
     * Check quantity type
     */
    private function checkQuantity($quantity)
    {
        if ( ! is_numeric($quantity) || $quantity < 0 )
            return 1;

        return (int)$quantity;
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
     * @return Collection
     */
    public function all()
    {
        $this->fetchMissingProductDataFromDb();

        return $this->items->map(function($item){
            return $this->mapProductData($item);
        });
    }

    /*
     * Fetch products/variants from db
     */
    public function fetchMissingProductDataFromDb()
    {
        $productIds = array_diff(
            $this->items->pluck(['id'])->toArray(),
            $this->loadedProducts->pluck('id')->toArray()
        );

        $productVariantsIds = array_diff(
            array_filter($this->items->pluck('variant_id')->toArray()),
            $this->loadedVariants->pluck('id')->toArray()
        );

        //If there is any non-fetched products
        if ( count($productIds) > 0 ) {
            $fechedProducts = Admin::getModelByTable('products')->basketSelect()
                                    ->whereIn('id', $productIds)->get();

            //Merge fetched products into existing collection
            $this->loadedProducts = $this->loadedProducts->merge($fechedProducts);
        }

        //If there is any non-fetched variants
        if ( count($productVariantsIds) > 0 ) {
            $fechedProducts = Admin::getModelByTable('products_variants')->basketSelect()
                                    ->whereIn('id', $productVariantsIds)->get();

            //Merge fetched products into existing collection
            $this->loadedVariants = $this->loadedVariants->merge($fechedProducts);
        }
    }

    /**
     * Add fetched product and variant into basket item
     *
     * @param  object  $item
     * @return object
     */
    public function mapProductData($item)
    {
        $item->product = $this->loadedProducts->find($item->id);

        if ( isset($item->variant_id) ) {
            $item->variant = $this->loadedVariants->find($item->variant_id);
        }

        return $item;
    }
}

?>