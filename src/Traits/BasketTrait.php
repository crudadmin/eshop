<?php

namespace AdminEshop\Traits;

use Admin;
use Store;
use Discounts;

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

    /**
     * Register basket discount into basket
     *
     * @param  string  $name
     * @param  string  $operator
     * @param  int/float  $value
     * @param  bool  $applyOnProducts
     * @param  mixed  $additional
     */
    public function addBasketDiscount(string $name, string $operator, $value, bool $applyOnProducts = false, $additional = null)
    {
        $this->discounts[$name] = [
            'name' => $name,
            'operator' => $operator,
            'value' => $value,
            'applyOnProducts' => $applyOnProducts,
            'additional' => $additional,
        ];
    }

    /**
     * Add fetched product and variant into basket item
     *
     * @param  object  $item
     * @param  array|null  $discounts
     * @return object
     */
    public function mapProductData($item, $discounts = null)
    {
        $item->product = $this->loadedProducts->find($item->id);

        if ( isset($item->variant_id) ) {
            $item->variant = $this->loadedVariants->find($item->variant_id);
        }

        Discounts::applyDiscounts(
            @$item->variant ?: $item->product,
            $discounts,
            function($discount, $item){
                return $discount->canApplyOnProductInBasket($item);
            }
        );

        return $item;
    }


    /**
     * Check if given key is with tax
     *
     * @param  string  $key
     * @return  bool
     */
    public function isDiscountableTaxSummaryKey($key)
    {
        //If is not discountable attribute
        if ( ! in_array($key, Discounts::getDiscountableAttributes()) )
            return;

        if ( strpos($key, 'WithTax') !== false )
            return true;

        if ( strpos($key, 'WithoutTax') !== false )
            return false;
    }

    /**
     * Get all available basket summary prices
     *
     * @param  Collection  $items
     * @return array
     */
    public function getSummary($items = null, $discounts = null)
    {
        $items = $items === null ? $this->all() : $items;

        $sum = [];

        foreach ($items as $basketItem) {
            $array = (@$basketItem->variant ?: $basketItem->product)->toArray();

            foreach ($array as $key => $value) {
                //If does not have price in attribute name
                if ( strpos(strtolower($key), 'price') === false ) {
                    continue;
                }

                if ( !array_key_exists($key, $sum) ) {
                    $sum[$key] = 0;
                }

                $sum[$key] = $basketItem->quantity * $array[$key];
            }
        }

        foreach ($sum as $key => $value) {
            foreach ($discounts as $discount) {
                if ( ! $discount->hasSummaryPriceOperator() ) {
                    continue;
                }

                //If is no withTax/withoutTax price atribute
                if ( ($isTax = $this->isDiscountableTaxSummaryKey($key)) === null ) {
                    continue;
                }

                $discountValue = $isTax ? Store::priceWithTax($discount->value) : $discount->value;

                $sum[$key] = operator_modifier($sum[$key], $discount->operator, $discountValue);
            }

            //Round numbers, and make sure all numbers are positive
            $sum[$key] = $sum[$key] < 0 ? 0 : $sum[$key];
            $sum[$key] = Store::roundNumber($sum[$key]);
        }

        return $sum;
    }
}

?>