<?php

namespace AdminEshop\Contracts\Concerns;

use Admin;
use Localization;

trait HasCurrencies
{
    private $rewritedCurrency;

    public function getCurrencyCode()
    {
        return $this->getCurrency()?->char ?: '€';
    }

    public function getCurrency()
    {
        return $this->rewritedCurrency ?: $this->cache('orders.currency', function(){
            if ( $languageCurrency = Localization::get()->currency ){
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
     *
     * @return  float
     */
    public function calculateFromDefaultCurrency($price)
    {
        if ( ($currency = $this->getCurrency()) && ($rate = $currency->rate) !== 1.0 ){
            $price = $this->roundNumber($price * $rate);
        }

        return $price;
    }
}