<?php

namespace AdminEshop\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class RulesServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('dic', function ($attribute, $value, $parameters, $validator)
        {
            $value = trim(mb_strtolower($value));

            return preg_match("/^[a-z]{2}[0-9]{7,10}$/", $value);
        });
    }
}
