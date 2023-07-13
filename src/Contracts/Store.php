<?php

namespace AdminEshop\Contracts;

use Admin;
use AdminEshop\Contracts\Concerns\HasCurrencies;
use AdminEshop\Contracts\Concerns\HasRoutes;
use AdminEshop\Contracts\Concerns\HasStoreAttributes;
use AdminEshop\Models\Attribute\AttributesUnit;
use AdminEshop\Models\Orders\OrdersProduct;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Store\Country;
use AdminEshop\Models\Store\Store as StoreModel;
use AdminEshop\Models\Store\Vat;
use Admin\Core\Contracts\DataStore;
use Localization;
use Cart;
use Log;

class Store
{
    use DataStore,
        HasRoutes,
        HasStoreAttributes,
        HasCurrencies;

    /*
     * Should eshop automatically show B2B prices?
     */
    private $hasB2B = false;

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
            $units = (Admin::getModel('AttributesUnit') ?: new AttributesUnit)->get();

            return $units->each->setLocalizedResponse();
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

    public function hasSummaryRounding()
    {
        return config('admineshop.round_summary', true);
    }

    /*
     * Returns prices in correct number format
     */
    public function numberFormat($number)
    {
        $separator = $this->getCurrency()->decimal_separator == 'comma' ? ',' : '.';

        return number_format($this->roundNumber($number), $this->getDecimalPlaces(), $separator, ' ');
    }

    /*
     * Return price in correct number format
     */
    public function priceFormat($number)
    {
        return $this->numberFormat($number). ' '. $this->getCurrencyCode();
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
     * @param  float    $price
     * @param  float    $vatValue
     * @param  boolean  $roudn
     */
    public function addVat($price, $vat, $round = true)
    {
        $price = $price * ($vat ? (1 + ($vat / 100)) : 1);

        return $round ? $this->roundNumber($price) : $price;
    }

    /**
     * Remove given vat value into number
     *
     * @param  float  $price
     * @param  float  $vatValue
     */
    public function removeVat($price, $vat, $round = null)
    {
        return $this->roundNumber(
            $price / ($vat ? (1 + ($vat / 100)) : 1),
            $round
        );
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

    public function getNuxtUrl($path)
    {
        if ( function_exists('getNuxtUrl') ){
            return getNuxtUrl($path);
        }

        $nuxtUrl = env('APP_NUXT_URL') ?: url('/');

        $path = !str_starts_with($path, '/') && $path ? '/'.$path : $path;

        //We want first and not default lenguage, because that is rewrited in CMS.
        $defaultLocaleSlug = Localization::getFirstLanguage()?->slug;

        $localeSlug = Localization::get()?->slug;

        return $nuxtUrl.($localeSlug == $defaultLocaleSlug ? '' : '/'.$localeSlug).$path;
    }

    public function log()
    {
        return Log::channel('store');
    }
}

?>