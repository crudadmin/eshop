<?php

namespace AdminEshop\Contracts\Synchronizer\Concerns;

use Admin;
use Store;

trait HasPricesTrait
{
    protected function prepareProductPrices($array, $row, $variant = false)
    {
        $array = $this->setDefaultProductPrices($array, $row);

        $array = $this->setPriceLevels($array, $row);

        return $array;
    }

    protected function setDefaultProductPrices($array, $row)
    {
        //Set default price columns
        $defaultCurrency = Store::getCurrency();

        $vat = $array['$vat_number'] = ($array['$vat_number'] ?? null)
                    ?: $this->getCurrencyVat($defaultCurrency, $row)
                    ?: Store::getDefaultVat();

        $price = $row['price_'.$defaultCurrency->code] ?? $row['price'] ?? null;
        $price = $this->castPriceNumber($price, $vat);

        if ( is_null($price) == false ){
            $array['price'] = $price;
        } else {
            unset($array['price']);
        }

        return $array;
    }

    protected function setPriceLevels($array, $row)
    {
        //Prepare price levels columns
        $prices = [];

        $codes = Store::getCurrencies();
        foreach ($codes as $currency)
        {
            //We does not want to save default currency into price levels
            if ( $currency->default ){
                continue;
            }

            $priceCode = 'price_'.$currency->code;

            if ( array_key_exists($priceCode, $row) ){
                $vat = $this->getCurrencyVat($currency, $row);

                $price = $this->castPriceNumber($row[$priceCode], $vat);

                if ( !is_null($price) ){
                    $prices[] = [
                        'price' => $price,
                        'currency_id' => $currency->getKey(),
                        'vat' => $vat,
                    ];
                }
            }
        }

        if ( count($prices) ){
            $array['$prices'] = $prices;
        }

        return $array;
    }

    protected function getCurrencyVat($currency, $row)
    {
        return (float)$row['vat_'.$currency->code];
    }

    public function castPriceNumber($number, $vat)
    {
        if ( $number == '' || is_null($number) ){
            return;
        }

        $number = str_replace(',', '.', $number);


        return (float)$number;
    }

    protected function getVatIdByValue($value)
    {
        $value = $value ?: 0;

        return $this->cache('vat.'.$value, function() use ($value){
            if ( $vat = Store::getVats()->where('vat', $value)->first() ){
                return $vat->getKey();
            }

            $vat = Admin::getModel('Vat')->create([
                'name' => $value.'%',
                'vat' => $value,
            ]);

            return $vat->getKey();
        });
    }
}