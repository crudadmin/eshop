<?php

namespace AdminEshop\Contracts;

use Admin;
use AdminEshop\Models\Orders\OrdersProduct;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Store\Country;
use AdminEshop\Models\Store\Store as StoreModel;
use AdminEshop\Models\Store\Tax;
use Admin\Core\Contracts\DataStore;
use Cart;

class Store
{
    use DataStore;

    /*
     * Should eshop automatically show B2B prices?
     */
    private $hasB2B = false;

    /*
     * Custom number roundings
     */
    private $rounding = null;

    /*
     * Return all taxes
     */
    public function getTaxes()
    {
        return $this->cache('taxes', function(){
            return (Admin::getModel('Tax') ?: new Tax)->get();
        });
    }

    /*
     * Return all countries
     */
    public function getCountries()
    {
        return $this->cache('countries', function(){
            return (Admin::getModel('Country') ?: new Country)->get();
        });
    }

    /*
     * Returns default tax value
     */
    public function getDefaultTax()
    {
        return $this->cache('tax.default', function(){
            $tax = $this->getTaxes()->where('default', true)->first();

            return $tax ? $tax->tax : 0;
        });
    }

    /**
     * Returns default tax value
     *
     * @param  int  $taxId
     * @return int/float
     */
    public function getTaxValueById($taxId)
    {
        return $this->cache('tax.'.$taxId, function() use ($taxId) {
            $tax = $this->getTaxes()->where('id', $taxId)->first();

            return $tax ? $tax->tax : 0;
        });
    }

    public function getSettings()
    {
        return $this->cache('storeSettings', function(){
            return (Admin::getModel('Store') ?: new StoreModel)->first();
        });
    }

    public function getCurrency()
    {
        return '€';
    }

    public function hasSummaryRounding()
    {
        return config('admineshop.round_summary', true);
    }

    public function getRounding()
    {
        if ( $this->rounding === false ){
            return false;
        }

        return $this->rounding ?: $this->getSettings()->rounding;
    }

    /**
     * Set custom number roundings
     *
     * @param  int|bool  $rounding
     */
    public function setRounding($rounding)
    {
        //If we want set default rounding set by eshop
        if ( $rounding === true ) {
            $rounding = null;
        }

        $this->rounding = $rounding;
    }

    /*
     * Round number by store price settings
     */
    public function roundNumber($number, $rounding = null)
    {
        $rounding = $rounding ?: $this->getRounding();

        //If we does not want rounding
        if ( $rounding === false ) {
            return $number;
        }

        return round($number, $rounding);
    }

    /*
     * Returns prices in correct number format
     */
    public function numberFormat($number)
    {
        return number_format($this->roundNumber($number), $this->getRounding(), '.', ' ');
    }

    /*
     * Returns prices in correct number format
     * For cart we want fixed 2 decimals
     */
    public function numberFormatWithoutTax($number)
    {
        return number_format($this->roundNumber($number, 2), 2, '.', ' ');
    }

    /*
     * Return price in correct number format
     */
    public function priceFormat($number)
    {
        return $this->numberFormat($number). ' '. $this->getCurrency();
    }

    /**
     * Add tax to given price
     *
     * @param  float/int  $price
     * @param  int/null  $taxId
     * @return float/int
     */
    public function priceWithTax($price, $taxId = null)
    {
        $taxes = $this->getTaxes();

        $tax = $taxId === null ? $this->getDefaultTax() : $this->getTaxValueById($taxId);

        return $this->addTax($price, $tax);
    }

    /**
     * Add given tax value into number
     *
     * @param  float  $price
     * @param  float  $taxValue
     */
    public function addTax($price, $tax)
    {
        return $this->roundNumber($price * ($tax ? (1 + ($tax / 100)) : 1));
    }

    /*
     * Show tax prices for B2b
     */
    public function hasB2B()
    {
        return session('b2b', $this->hasB2B);
    }

    /*
     * Set b2b prices
     */
    public function setB2B($tax = false)
    {
        session()->put('b2b', $tax);
        session()->save();

        $this->hasB2B = $tax;
    }

    /**
     * Filter config by given key
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return  array
     */
    public function filterConfig(string $configKey, $value)
    {
        $arr = [];

        foreach (config('admineshop.product_types') as $key => $item) {
            if ( @$item[$configKey] === $value ) {
                $arr[] = $key;
            }
        }

        return $arr;
    }

    /**
     * Which product cant consists of variants
     *
     * @return  array
     */
    function nonVariantsProductTypes()
    {
        return $this->filterConfig('variants', false);
    }

    /**
     * Which product types are orderable
     *
     * @return  array
     */
    function orderableProductTypes()
    {
        return $this->filterConfig('orderableVariants', false);
    }
}

?>