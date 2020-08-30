<?php

namespace AdminEshop\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationRuleParser;
use Store;

class RulesServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->addProductsValidators();
    }

    public function addProductsValidators()
    {
        Validator::extend('positivePriceIfRequired', function ($attribute, $value, $parameters, $validator) {
            $type = $parameters[0];

            //If is non orderable product type, just continue...
            if ( $type == 'products' && !in_array(request('product_type'), Store::orderableProductTypes()) ) {
                return true;
            }

            //If is orderable product type, just continue...
            if ( $type == 'variants' && in_array(request('product_type'), Store::orderableProductTypes()) ) {
                return true;
            }

            //We need set positive price for this product type
            if ( ! $value || $value == 0 ) {
                return false;
            }

            return true;
        }, _('Cena produktu musí byť kladná.'));

        Validator::extend('zipcode', function ($attribute, $value, $parameters, $validator) {
            $value = str_replace(' ', '', $value);

            return is_numeric($value) && strlen($value) === 5;
        }, _('PSČ musí byť zadané v tvare 000 00.'));

        Validator::extend('company_id', function ($attribute, $number, $parameters, $validator) {
            // be liberal in what you receive
            $ic = preg_replace('#\s+#', '', $number);

            // má požadovaný tvar?
            if (!preg_match('#^\d{8}$#', $ic)) {
                return FALSE;
            }

            // kontrolní součet
            $a = 0;
            for ($i = 0; $i < 7; $i++) {
                $a += $ic[$i] * (8 - $i);
            }

            $a = $a % 11;
            if ($a === 0) {
                $c = 1;
            } elseif ($a === 1) {
                $c = 0;
            } else {
                $c = 11 - $a;
            }

            return (int) $ic[7] === $c;
        }, _('Pole musí byť v tvare platného IČO.'));
    }
}
