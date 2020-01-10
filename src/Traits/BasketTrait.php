<?php

namespace AdminEshop\Traits;

use Admin;

trait BasketTrait
{
    /*
     * Which items has been added into basket
     */
    public $addedItems = [];

    /*
     * Which items has been updated in basket
     */
    public $updatedItems = [];

    public function pushToAdded($item)
    {
        $this->addedItems[] = $item;
    }

    public function pushToUpdated($item)
    {
        $this->updatedItems[] = $item;
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
     * Check quantity type
     */
    private function checkQuantity($quantity)
    {
        if ( ! is_numeric($quantity) || $quantity < 0 )
            return 1;

        return (int)$quantity;
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
                                    ->whereIn('products.id', $productIds)->get();

            //Merge fetched products into existing collection
            $this->loadedProducts = $this->loadedProducts->merge($fechedProducts);
        }

        //If there is any non-fetched variants
        if ( count($productVariantsIds) > 0 ) {
            $fechedVariants = Admin::getModelByTable('products_variants')->basketSelect()
                                    ->with(['attributesItems'])
                                    ->whereIn('products_variants.id', $productVariantsIds)->get();

            //Merge fetched products into existing collection
            $this->loadedVariants = $this->loadedVariants->merge($fechedVariants);
        }
    }

    public function applyCodeDiscounts($item, $code)
    {
        if ( $code->discount_percent ) {
            $item->product->addDiscount('-%', $code->discount_percent);
        }
    }

    public function applyAllDiscounts($item)
    {
        if ( $code = $this->getDiscountCode() ) {
            $this->applyCodeDiscounts($item, $code);
        }

        return $item;
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

        $this->applyAllDiscounts($item);

        return $item;
    }
}

?>