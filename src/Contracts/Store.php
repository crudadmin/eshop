<?php

namespace AdminEshop\Contracts;

use Admin;
use AdminEshop\Contracts\Concerns\HasRoutes;
use AdminEshop\Contracts\Concerns\HasStoreAttributes;
use AdminEshop\Models\Attribute\AttributesUnit;
use AdminEshop\Models\Orders\OrdersProduct;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Store\Country;
use AdminEshop\Models\Store\Store as StoreModel;
use AdminEshop\Models\Store\Vat;
use Admin\Core\Contracts\DataStore;
use Cart;

class Store
{
    use DataStore,
        HasRoutes,
        HasStoreAttributes;

    /*
     * Should eshop automatically show B2B prices?
     */
    private $hasB2B = false;

    /*
     * Custom price number settings
     */
    private $rounding = null;
    private $decimalPlaces = null;

    /*
     * Return all vats
     */
    public function getVats()
    {
        return $this->cache('vats', function(){
            return (Admin::getModel('Vat') ?: new Vat)->get();
        });
    }

    /*
     * Return all vats
     */
    public function getUnits()
    {
        return $this->cache('units', function(){
            return (Admin::getModel('AttributesUnit') ?: new AttributesUnit)->get();
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
     * Return order statuses
     */
    public function getOrdersStatuses()
    {
        return $this->cache('orders.statuses', function(){
            return Admin::getModel('OrdersStatus')->get();
        });
    }

    /*
     * Returns default vat value
     */
    public function getDefaultVat()
    {
        if ( app()->runningInconsole() === true ) {
            return 0;
        }

        return $this->cache('vat.default', function(){
            $vat = $this->getVats()->where('default', true)->first();

            return $vat ? $vat->vat : 0;
        });
    }

    /**
     * Returns default vat value
     *
     * @param  int  $vatId
     * @return int/float
     */
    public function getVatValueById($vatId)
    {
        return $this->cache('vat.'.$vatId, function() use ($vatId) {
            $vat = $this->getVats()->where('id', $vatId)->first();

            return $vat ? $vat->vat : 0;
        });
    }

    public function getUnit($unitId)
    {
        return $this->cache('unit.'.$unitId, function() use ($unitId) {
            return $this->getUnits()->where('id', $unitId)->first();
        });
    }

    public function getOrdersStatus($statusId)
    {
        return $this->cache('unit.'.$statusId, function() use ($statusId) {
            return $this->getOrdersStatuses()->where('id', $statusId)->first();
        });
    }

    public function getSettings()
    {
        return $this->cache('storeSettings', function(){
            return (Admin::getModel('Store') ?: new StoreModel)->first() ?: new StoreModel;
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

        if ( $this->rounding ) {
            return $this->rounding;
        }

        //We need cache rounding value for better performance
        return $this->cache('store.rounding', function(){
            return (int)$this->getSettings()->decimal_rounding;
        });
    }

    public function getDecimalPlaces()
    {
        if ( $this->decimalPlaces === false ){
            return false;
        }

        if ( $this->decimalPlaces ) {
            return $this->decimalPlaces;
        }

        //We need cache decimalPlaces value for better performance
        return $this->cache('store.decimalPlaces', function(){
            return (int)$this->getSettings()->decimal_places;
        });
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
        $separator = $this->getSettings()->decimal_separator == 'comma' ? ',' : '.';

        return number_format($this->roundNumber($number), $this->getDecimalPlaces(), $separator, ' ');
    }

    /*
     * Return price in correct number format
     */
    public function priceFormat($number)
    {
        return $this->numberFormat($number). ' '. $this->getCurrency();
    }

    /**
     * Add vat to given price
     *
     * @param  float/int  $price
     * @param  int/null  $vatId
     * @return float/int
     */
    public function priceWithVat($price, $vatId = null)
    {
        $vat = $vatId === null ? $this->getDefaultVat() : $this->getVatValueById($vatId);

        return $this->addVat($price, $vat);
    }

    /**
     * Add given vat value into number
     *
     * @param  float  $price
     * @param  float  $vatValue
     */
    public function addVat($price, $vat)
    {
        return $this->roundNumber($price * ($vat ? (1 + ($vat / 100)) : 1));
    }

    /*
     * Show vat prices for B2b
     */
    public function hasB2B()
    {
        return $this->cache('hasB2B', function(){
            return Cart::getDriver()->get('b2b', $this->hasB2B);
        });
    }

    /*
     * Set b2b prices
     */
    public function setB2B($vat = false)
    {
        Cart::getDriver()->set('b2b', $vat);

        $this->hasB2B = $vat;
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
     * Which product can consists of variants
     *
     * @return  array
     */
    function variantsProductTypes()
    {
        return $this->filterConfig('variants', true);
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

    public function isEnabledLocalization()
    {
        return Admin::isEnabledLocalization() && config('admineshop.localization', false) === true;
    }
}

?>