<?php

namespace AdminEshop\Helpers;

use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Orders\OrdersProduct;
use Cache;
use Admin;
use DB;

class Store
{
    private $storeSettings;

    private $taxes;

    private $hasB2B = false;

    /*
     * Return all taxes
     */
    public function getTaxes()
    {
        if ( ! $this->taxes ) {
            $this->taxes = Admin::getModel('Tax')->pluck('tax', 'id')->toArray();
        }

        return $this->taxes;
    }

    public function getSettings()
    {
        if ( ! $this->storeSettings ) {
            $this->storeSettings = Admin::getModel('Store')->first();
        }

        return $this->storeSettings;
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
     * For basket we want fixed 2 decimals
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

    /*
     * Add tax to given price
     */
    public function priceWithTax($price, $tax_id)
    {
        $taxes = $this->getTaxes();

        $tax = array_key_exists($tax_id, $taxes) ? $taxes[$tax_id] : null;

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
}

?>