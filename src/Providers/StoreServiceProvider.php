<?php

namespace AdminEshop\Providers;

use Cmixin\BusinessDay;
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
        $this->app->bind('cart.driver', \AdminEshop\Contracts\Cart\Drivers\CartDriver::class);
        $this->app->bind('order.service', \AdminEshop\Contracts\OrderService::class);

        $this->initBusinessDates();
    }

    private function initBusinessDates()
    {
        BusinessDay::enable(
            'Illuminate\Support\Carbon',
            config('admineshop.holidays.country', 'sk'),
            config('admineshop.holidays.additional', [])
        );
    }
}