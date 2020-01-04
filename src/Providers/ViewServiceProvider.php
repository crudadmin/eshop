<?php

namespace AdminEshop\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Admin;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../Views', 'admineshop');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
