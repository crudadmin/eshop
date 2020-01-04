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
        $this->app->bind('store', \AdminEshop\Helpers\Store::class);
    }
}