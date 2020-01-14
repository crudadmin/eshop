<?php

namespace AdminEshop\Contracts;

use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Orders\OrdersProduct;
use Admin\Core\Contracts\DataStore;
use Admin;
use Cart;

class Store
{
    use DataStore;

    /*
     * Should eshop automatically show B2B prices?
     */
    private $hasB2B = false;

    /*
     * Return all taxes
     */
    public function getTaxes()
    {
        return $this->cache('taxes', function(){
            return Admin::getModel('Tax')->get();
        });
    }

    /*
     * Return all countries
     */
    public function getCountries()
    {
        return $this->cache('countries', function(){
            return Admin::getModel('Country')->get();
        });
    }

    /*
     * Return all payment methods
     */
    public function getPaymentMethods()
    {
        return $this->cache('paymentMethods', function(){
            return Admin::getModel('PaymentsMethod')->get();
        });
    }

    /*
     * Return all payment methods
     */
    public function getDeliveries()
    {
        return $this->cache('deliveries', function(){
            return Admin::getModel('Delivery')->get();
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
            return Admin::getModel('Store')->first();
        });
    }

    public function getCurrency()
    {
        return '€';
    }

    public function getRounding()
    {
        return $this->getSettings()->rounding;
    }

    /*
     * Round number by store price settings
     */
    public function roundNumber($number, $rounding = null)
    {
        return round($number, $rounding ?: $this->getRounding());
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

        return Store::roundNumber($price * ($tax ? (1 + ($tax / 100)) : 1));
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

    /**
     *  Return payment methods for selected delivery
     *
     * @return  array
     */
    public function getPaymentMethodsByDelivery()
    {
        $delivery = Cart::getSelectedDelivery();

        $allowedPaymentMethods = $delivery->payments()->pluck('payments_methods.id')->toArray();

        //If any rule is present, allow all payment methods
        if ( count($allowedPaymentMethods) == 0 ) {
            return $this->getPaymentMethods();
        }

        return $this->getPaymentMethods()->filter(function($item) use ($allowedPaymentMethods) {
            return in_array($item->getKey(), $allowedPaymentMethods);
        });
    }
}

?>