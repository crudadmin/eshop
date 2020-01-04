<?php
namespace AdminEshop\Providers;

use Illuminate\Support\ServiceProvider;

class BasketServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('basket', \AdminEshop\Helpers\Basket::class);
    }
}