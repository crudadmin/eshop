<?php
namespace AdminEshop\Providers;

use Illuminate\Support\ServiceProvider;

class StoreServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('store', \AdminEshop\Contracts\Store::class);
        $this->app->bind('store.discounts', \AdminEshop\Contracts\Discounts::class);
        $this->app->bind('cart', \AdminEshop\Contracts\Cart::class);
        $this->app->bind('order.service', \AdminEshop\Contracts\OrderService::class);
    }
}