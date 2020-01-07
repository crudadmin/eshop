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
     * Return price in correct number format
     */
    public function priceWithoutTax($number)
    {
        return $this->numberFormatWithoutTax($number). ' '. $this->getCurrency();
    }
}

?>