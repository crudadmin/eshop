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

    private $loadedProducts = null;

    private $loadedVariants = null;

    /*
     * Session key
     */
    private $key = 'basket_items';

    public function __construct()
    {
        $this->items = new Collection($this->getItemsFromSession());

        $p = new Product(['id' => 50]);
        $p->id = 1;

        $this->loadedProducts = new Collection([ $p ]);

        $this->loadedVariants = new Collection();
    }

    private function getItemsFromSession()
    {
        $items = session($this->key, []);

        if ( ! is_array($items) )
            return [];

        return array_map(function($item){
            return (object)$item;
        }, $items);
    }

    /**
     * Add product into basket and save it into session
     * @param Product     $product
     * @param int|integer $quantity
     */
    public function add(int $productId, int $quantity = 1, $variantId = null)
    {
        $productId = (int)$productId;

        if ( $variantId ) {
            $variantId = (int)$variantId;
        }

        //If items does not exists in basket
        if ( ! $this->items->has($productId) )
        {
            $item = new \StdClass();
            $item->id = $productId;
            $item->quantity = $variantId ? 0 : $quantity;
            $item->variants = [];

            //Add variant if is selected
            if ( $variantId ) {
                $item->variants[$variantId] = $this->checkQuantity($quantity);
            }

            $this->items[ $productId ] = $item;
        } else {
            if ( $variantId ){
                //Add new variant into product
                if ( ! array_key_exists($variantId, $this->items[ $productId ]->variants) ) {
                    $this->items[ $productId ]->variants[$variantId] = $this->checkQuantity($quantity);
                }

                //Update existing variant
                else {
                    $this->items[ $productId ]->variants[$variantId] += $this->checkQuantity($quantity);
                }
            } else {
                //Update simple product quantity
                $this->items[ $productId ]->quantity += $this->checkQuantity($quantity);
            }
        }

        $this->save();

        return $this;
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
     * Save items in basket
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
     * Get all items
     *
     * @return Collection
     */
    public function all()
    {
        $this->loadMissingProductDataFromDb();

        return $this->items->map(function($item){
            return $this->mapProductData($item);
        });
    }

    public function loadMissingProductDataFromDb()
    {
        $productIds = array_diff(array_keys($this->items->toArray()), $this->loadedProducts->pluck('id')->toArray());
        $productVariantsIds = [];

        //If there is any non-fetched products
        if ( count($productIds) > 0 ) {
            $fechedProducts = Admin::getModelByTable('products')
                                    ->basketSelect()
                                    ->whereIn('id', $productIds)
                                    ->get();

            //Merge fetched products into existing collection
            $this->loadedProducts = $this->loadedProducts->merge($fechedProducts);
        }
    }

    public function mapProductData($item)
    {
        $item->product = $this->loadedProducts->find($item->id);

        return $item;
    }
}

?>