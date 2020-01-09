<?php

namespace AdminEshop\Eloquent\Concerns;

use Store;

trait PriceMutator
{
    /*
     * Has product price with Tax?
     */
    public function showTaxPrices()
    {
        return Store::hasB2B() ? false : true;
    }

    /*
     * Price without TAX after discounts
     */
    public function getPriceWithDiscountsAttribute()
    {
        return operator_modifier($this->price, $this->discount_operator, $this->discount);
    }

    /*
     * Returns price with discounts but without tax
     */
    public function getPriceWithoutTaxAttribute()
    {
        return Store::roundNumber($this->priceWithDiscounts);
    }

    /*
     * Return price with tax & discounts
     */
    public function getPriceWithTaxAttribute()
    {
        return Store::priceWithTax($this->priceWithDiscounts, $this->tax_id);
    }

    /**
     * Return B2B or B2C price by client settings
     *
     * @return float
     */
    public function getFinalPriceAttribute()
    {
        if ( $this->showTaxPrices() ) {
            return $this->priceWithTax;
        }

        return $this->priceWithoutTax;
    }

    /*
     * Price without TAX after discounts
     */
    public function getDefaultPriceWithoutTaxAttribute()
    {
        return Store::roundNumber($this->price);
    }

    /*
     * Price without TAX after discounts
     */
    public function getDefaultPriceWithTaxAttribute()
    {
        return Store::priceWithTax($this->defaultPriceWithoutTax, $this->tax_id);
    }

    /**
     * Return B2B or B2C price by client settings
     *
     * @return float
     */
    public function getDefaultFinalPriceAttribute()
    {
        if ( $this->showTaxPrices() ) {
            return $this->defaultPriceWithTax;
        }

        return $this->defaultPriceWithoutTax;
    }
}