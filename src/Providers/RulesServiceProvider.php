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
    }
}
