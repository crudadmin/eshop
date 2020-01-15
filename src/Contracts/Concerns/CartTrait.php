<?php

namespace AdminEshop\Contracts\Concerns;

use \Illuminate\Database\Eloquent\Collection;
use AdminEshop\Contracts\CartItem;
use Discounts;
use Admin;
use Store;

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

    /*
     * Fetched products from db
     */
    private $fetchedProducts;

    /*
     * Fetched variants from db
     */
    private $fetchedVariants;

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

        if ( ! is_array($items) ) {
            return [];
        }

        return new Collection(array_map(function($item){
            return new CartItem($item['id'], $item['quantity'], @$item['variant_id']);
        }, $items));
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

    /**
     * Fetch products/variants from db
     *
     * @return  this
     */
    public function fetchMissingProductDataFromDb()
    {
        $productIds = array_diff(
            $this->items->pluck(['id'])->toArray(),
            $this->getFetchedProducts()->pluck('id')->toArray()
        );

        $productVariantsIds = array_diff(
            array_filter($this->items->pluck('variant_id')->toArray()),
            $this->getFetchedVariants()->pluck('id')->toArray()
        );

        //If there is any non-fetched products
        if ( count($productIds) > 0 ) {
            $fechedProducts = Admin::getModelByTable('products')->cartSelect()
                                    ->whereIn('products.id', $productIds)->get();

            //Merge fetched products into existing collection
            $this->fetchedProducts = $this->getFetchedProducts()->merge($fechedProducts);
        }

        //If there is any non-fetched variants
        if ( count($productVariantsIds) > 0 ) {
            $fechedVariants = Admin::getModelByTable('products_variants')->cartSelect()
                                    ->with(['attributesItems'])
                                    ->whereIn('products_variants.id', $productVariantsIds)->get();

            //Merge fetched products into existing collection
            $this->fetchedVariants = $this->getFetchedVariants()->merge($fechedVariants);
        }

        return $this;
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

        return Discounts::getDiscountableAttributeTaxValue($key);
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
     * Add additional prices into order sum, as
     *
     * @param  int/float  $price If is no withTax/withoutTax price atribute
     * @param  bool  $isTax
     */
    public function addAdditionalPaymentsIntoSum($price, bool $isTax)
    {
        $selectedDelivery = $this->getSelectedDelivery();
        $selectedPaymentMethod = $this->getSelectedPaymentMethod();

        //Add delivery
        if ( $selectedDelivery ) {
            $price += $selectedDelivery->{$isTax ? 'priceWithTax' : 'priceWithoutTax'};
        }

        if ( $selectedPaymentMethod ) {
            $price += $selectedPaymentMethod->{$isTax ? 'priceWithTax' : 'priceWithoutTax'};
        }

        return $price;
    }

    /**
     * Apply given discounts on whole sum
     *
     * @param  int/float  $price
     * @param  array  $discounts
     * @param  bool/null  $isTax
     *
     * @return int/float
     */
    public function addDiscountsIntoFinalSum($price, $discounts, $isTax = null)
    {
        foreach ($discounts as $discount) {
            //If this discount is not applied on whole cart,
            //Or is not discountableTax attribute
            if ( $discount->applyOnWholeCart() !== true || $isTax === null ) {
                continue;
            }

            //If is tax attribute, and discount value is with + or - operator
            //Then we need to apply tax to this discount
            $discountValue = $isTax === true && $discount->hasSumPriceOperator() ?
                                    Store::priceWithTax($discount->value) : $discount->value;

            //Apply given discount
            $price = operator_modifier($price, $discount->operator, $discountValue);
        }

        return $price;
    }

    /**
     * Get all available cart summary prices with discounts
     *
     * @param  Collection  $items
     * @param  array  $discounts
     * @param  bool  $$fullCartResponse - add payment and delivery prices into sum
     * @return array
     */
    public function getSummary($items = null, $discounts = null, $fullCartResponse = false)
    {
        $items = $items === null ? $this->all() : $items;

        $discounts = $discounts === null ? Discounts::getDiscounts() : $discounts;

        $sum = $this->getDefaultSummary($items);

        foreach ($sum as $key => $value) {
            //Check if we can apply sum modifications into this key
            $isTax = $this->isDiscountableTaxSummaryKey($key);

            //Add statics discount int osummary
            $sum[$key] = $this->addDiscountsIntoFinalSum($sum[$key], $discounts, $isTax);

            //Add delivery, payment method prices etc...
            if ( $fullCartResponse === true && $isTax !== null ) {
                $sum[$key] = $this->addAdditionalPaymentsIntoSum($sum[$key], $isTax);
            }

            //Round numbers, and make sure all numbers are positive
            $sum[$key] = $sum[$key] < 0 ? 0 : $sum[$key];
            $sum[$key] = Store::roundNumber($sum[$key]);
        }

        return $sum;
    }

    /**
     * Returns fetched products
     *
     * @return  Collection
     */
    public function getFetchedProducts()
    {
        return $this->fetchedProducts ?: new Collection([]);
    }

    /**
     * Returns fetched variants
     *
     * @return  Collection
     */
    public function getFetchedVariants()
    {
        return $this->fetchedVariants ?: new Collection([]);
    }
}

?>