<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\Discounts\Discount;
use Store;
use StoreDiscounts;

trait PriceMutator
{
    /**
     * Prices levels
     * *
     *
     * initialPriceWithTax / initialPriceWithoutTax / initialClientPrice - initial price without any discount
     * defaultPriceWithTax / defaultPriceWithoutTax / defaultClientPrice - initial price with product discount
     * priceWithTax / priceWithoutTax / clientPrice - price with all prossible discounts
     */

    /**
     * Here will be stored all additional products discount from basket
     *
     * @var  array
     */
    protected $registredDiscounts = [];

    /**
     * Add product discount
     *
     * @param  AdminEshop\Contracts\Discounts\Discount  $discount
     */
    public function addDiscount(Discount $discount)
    {
        $this->registredDiscounts[$discount->getDiscountName()] = $discount;
    }

    /**
     * Apply given discounts on given price
     *
     * @param  float/int  $price
     * @param  array/null $discounts (null = all)
     * @return float/ing
     */
    public function applyDiscounts($price, $discounts = null)
    {
        StoreDiscounts::applyDiscounts($this, $discounts, function($discount){
            return $discount->canApplyOnProduct($this);
        });

        $allowedDiscounts = array_map(function($discount){
            return $discount->getDiscountName();
        }, $discounts ?: []);

        //Apply all discounts into final price
        foreach ($this->registredDiscounts as $discount) {
            //Skip non allowed discounts
            if ( $discounts === null || in_array($discount->getDiscountName(), $allowedDiscounts) ) {
                $value = is_callable($discount->value) ? $discount->value() : $discount->value;

                $price = operator_modifier($price, $discount->operator, $value);
            }

        }

        return $price;
    }

    /*
     * Has product price with Tax?
     */
    public function showTaxPrices()
    {
        return Store::hasB2B() ? false : true;
    }

    /*
     * Return pure default product price without all discounts and without TAX
     */
    public function getInitialPriceWithoutTaxAttribute()
    {
        return Store::roundNumber($this->price);
    }

    /*
     * Return pure default product price without all discounts, with TAX
     */
    public function getInitialPriceWithTaxAttribute($value)
    {
        return Store::priceWithTax($this->initialPriceWithoutTax, $this->tax_id);
    }

    /*
     * Price without TAX after initial product discounts
     */
    public function getDefaultPriceWithoutTaxAttribute()
    {
        $price = operator_modifier($this->price, $this->discount_operator, $this->discount);

        return Store::roundNumber($price);
    }

    /*
     * Price without TAX after discounts
     */
    public function getDefaultPriceWithTaxAttribute()
    {
        return Store::priceWithTax($this->defaultPriceWithoutTax, $this->tax_id);
    }

    /*
     * Returns price with discounts but without tax
     */
    public function getPriceWithoutTaxAttribute()
    {
        $price = $this->applyDiscounts($this->defaultPriceWithoutTax);

        return Store::roundNumber($price);
    }

    /*
     * Return price with tax & discounts
     */
    public function getPriceWithTaxAttribute()
    {
        return Store::priceWithTax($this->priceWithoutTax, $this->tax_id);
    }

    /**
     * Return B2B or B2C initial product price by client settings
     *
     * @return float
     */
    public function getInitialClientPriceAttribute()
    {
        if ( $this->showTaxPrices() ) {
            return $this->initialPriceWithTax;
        }

        return $this->initialPriceWithoutTax;
    }

    /**
     * Return B2B or B2C with default product discount price by client settings
     *
     * @return float
     */
    public function getDefaultClientPriceAttribute()
    {
        if ( $this->showTaxPrices() ) {
            return $this->defaultPriceWithTax;
        }

        return $this->defaultPriceWithoutTax;
    }

    /**
     * Return B2B or B2C price with all available discount by client settings
     *
     * @return float
     */
    public function getClientPriceAttribute()
    {
        if ( $this->showTaxPrices() ) {
            return $this->priceWithTax;
        }

        return $this->priceWithoutTax;
    }
}