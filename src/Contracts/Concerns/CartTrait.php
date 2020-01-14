<?php

namespace AdminEshop\Contracts\Concerns;

use Admin;
use Store;
use Discounts;
use Illuminate\Support\Collection;

trait CartTrait
{
    /*
     * Which items has been added into cart
     */
    public $addedItems = [];

    /*
     * Which items has been updated in cart
     */
    public $updatedItems = [];

    /**
     * Items has been added into cart
     *
     * @param  object  $item
     * @return  this
     */
    public function pushToAdded($item)
    {
        $this->addedItems[] = $item;

        return $this;
    }

    /**
     * Items has been updated into cart
     *
     * @param  object  $item
     * @return  this
     */
    public function pushToUpdated($item)
    {
        $this->updatedItems[] = $item;

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
            $fechedProducts = Admin::getModelByTable('products')->cartSelect()
                                    ->whereIn('products.id', $productIds)->get();

            //Merge fetched products into existing collection
            $this->loadedProducts = $this->loadedProducts->merge($fechedProducts);
        }

        //If there is any non-fetched variants
        if ( count($productVariantsIds) > 0 ) {
            $fechedVariants = Admin::getModelByTable('products_variants')->cartSelect()
                                    ->with(['attributesItems'])
                                    ->whereIn('products_variants.id', $productVariantsIds)->get();

            //Merge fetched products into existing collection
            $this->loadedVariants = $this->loadedVariants->merge($fechedVariants);
        }
    }

    /**
     * Add fetched product and variant into cart item
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

        $this->addCartDiscountsIntoModel(@$item->variant ?: $item->product, $discounts);

        return $item;
    }

    /**
     * Add cart discounts into model
     *
     * @param  AdminModel  $item
     * @param  array|null  $discounts
     */
    public function addCartDiscountsIntoModel($itemOrItems = null, $discounts = null)
    {
        //Item or items must be present
        if ( ! $itemOrItems ) {
            return $itemOrItems;
        }

        $items = ($itemOrItems instanceof Collection) ? $itemOrItems : collect([ $itemOrItems ]);

        foreach ($items as $row) {
            Discounts::applyDiscountsOnModel(
                $row,
                $discounts,
                function($discount, $item){
                    return $discount->canApplyInCart($item);
                }
            );
        }

        return $itemOrItems;
    }


    /**
     * Check if given key is with tax
     *
     * @param  string  $key
     * @return  bool
     */
    public function isDiscountableTaxSummaryKey($key)
    {
        if ( strpos($key, 'WithTax') !== false )
            return true;

        if ( strpos($key, 'WithoutTax') !== false )
            return false;

        //If is not discountable attribute by withTax/WithouTax
        //try other dynamic fields from discounts settings
        if ( in_array($key, Discounts::getDiscountableAttributes()) ) {
            return 0;
        }
    }

    /**
     * Get all available cart summary prices
     *
     * @param  Collection  $items
     * @return array
     */
    public function getDefaultSummary($items)
    {
        $sum = [];

        foreach ($items as $cartItem) {
            $array = (@$cartItem->variant ?: $cartItem->product)->toArray();

            foreach ($array as $key => $value) {
                //If does not have price in attribute name
                if ( strpos(strtolower($key), 'price') === false ) {
                    continue;
                }

                if ( !array_key_exists($key, $sum) ) {
                    $sum[$key] = 0;
                }

                $sum[$key] = $cartItem->quantity * $array[$key];
            }
        }

        return $sum;
    }

    /**
     * Get all available cart summary prices with discounts
     *
     * @param  Collection  $items
     * @return array
     */
    public function getSummary($items = null, $discounts = null)
    {
        $items = $items === null ? $this->all() : $items;

        $discounts = $discounts === null ? Discounts::getDiscounts() : $discounts;

        $sum = $this->getDefaultSummary($items);

        foreach ($sum as $key => $value) {
            foreach ($discounts as $discount) {
                //Apply this discount only
                if ( $discount->applyOnWholeCart() !== true ) {
                    continue;
                }

                //If is no withTax/withoutTax price atribute
                if ( ($isTax = $this->isDiscountableTaxSummaryKey($key)) === null ) {
                    continue;
                }

                //If is tax attribute, and discount value is with + or - operator
                //Then we need to add tax to this discount
                $discountValue = $isTax === true && $discount->hasSumPriceOperator() ?
                                    Store::priceWithTax($discount->value)
                                    : $discount->value;

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