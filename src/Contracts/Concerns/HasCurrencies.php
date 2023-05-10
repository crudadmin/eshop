<?php

namespace AdminEshop\Contracts\Concerns;

use Admin;
use Localization;

trait HasCurrencies
{
    private $rewritedCurrency;

    /*
     * Custom price number settings
     */
    private $rounding = null;
    private $decimalPlaces = null;

    public function getCurrencyCode()
    {
        return $this->getCurrency()?->char ?: '€';
    }

    public function getCurrency()
    {
        return $this->rewritedCurrency ?: $this->cache('orders.currency', function(){
            if ( Admin::isFrontend() && $languageCurrency = Localization::get()->currency ){
                return $languageCurrency;
            }

            //Returns default currency
            return $this->getCurrencies()->firstWhere('default', true);
        });
    }

    public function setCurrency($currency)
    {
        $this->rewritedCurrency = $currency;

        return $this;
    }

    public function getCurrencies()
    {
        return $this->cache('orders.currencies', function(){
            return Admin::getModel('Currency')->get();
        });
    }

    /**
     * Recalculate given default price into selected currency
     *
     * @param  float  $price
     * @param  int  $currencyId
     * @param  int  $backward
     *
     * @return  float
     */
    public function calculateFromDefaultCurrency($price, $defaultCurrencyId = null, $backward = false)
    {
        if (
            //If currency is available
            ($currency = $this->getCurrency())

            //If selected currency is not same as given currency
            && $defaultCurrencyId !== $currency->getKey()

            //If rate is not flat
            && ($rate = $currency->getAttribute('rate')) !== 1.0
        ){
            $price = $backward == true
                        ? $price / $rate
                        : $price * $rate;

            $price = $this->roundNumber($price);
        }

        return $price;
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
            return (int)$this->getCurrency()->decimal_rounding;
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
            return (int)$this->getCurrency()->decimal_places;
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
        $rounding = $rounding === false ? $rounding : ($rounding ?: $this->getRounding());

        //If we does not want rounding
        if ( $rounding === false ) {
            return $number;
        }

        return round($number, $rounding);
    }
}