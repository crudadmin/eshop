<?php

namespace AdminEshop\Eloquent\Concerns;

use Store;

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

    /*
     * Here will be stored all additional products discount from basket
     */
    protected $availableProductDiscounts = [];

    /**
     * Add product discount
     *
     * @param  string  $type
     * @param  integer/float/callable  $value
     */
    public function addDiscount(string $name, string $type, $value)
    {
        $this->availableProductDiscounts[$name] = [
            'operator' => $type,
            'value' => $value,
        ];
    }

    /**
     * Apply given discounts on given price
     *
     * @param  float/int  $price
     * @param  array/null  $allowedDiscounts
     * @param  array  $exceptDiscounts
     * @return float/ing
     */
    public function applyDiscounts($price, $allowedDiscounts = null, $exceptDiscounts = [])
    {
        $exceptDiscounts = array_wrap($exceptDiscounts);

        //Apply all registered discounts
        if ( $allowedDiscounts === null ) {
            $allowedDiscounts = array_keys($this->availableProductDiscounts);
        }

        //Apply all discounts into final price
        foreach ($this->availableProductDiscounts as $name => $item) {
            //Skip non allowed discounts
            if ( in_array($name, $allowedDiscounts) && !in_array($name, $exceptDiscounts) ) {
                $value = is_callable($item['value']) ? $item['value']() : $item['value'];

                $price = operator_modifier($price, $item['operator'], $value);
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