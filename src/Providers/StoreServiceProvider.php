<?php

namespace AdminEshop\Providers;

use AdminEshop\Jobs\CleanEmptyCartTokensJob;
use AdminEshop\Jobs\ProductAvaiabilityChecker;
use AdminEshop\Jobs\SetOrderStatusAfterInactivness;
use Cmixin\BusinessDay;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use PaymentService;

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

    public function boot()
    {
        PaymentService::setPaymentUrl(function($path, $host){
            return nuxtUrl($path, $host);
        });

        $this->registerSchedules();
    }

    private function initBusinessDates()
    {
        BusinessDay::enable(
            'Illuminate\Support\Carbon',
            config('admineshop.holidays.country', 'sk'),
            config('admineshop.holidays.additional', [])
        );
    }

    public function registerSchedules()
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->job(new CleanEmptyCartTokensJob)->dailyAt('04:00');
            $schedule->job(new SetOrderStatusAfterInactivness)->dailyAt('05:55');

            if ( $scheduleAt = config('admineshop.stock.stock_notifier_scheduler') ) {
                $schedule->job(new ProductAvaiabilityChecker)->{$scheduleAt}();
            }
        });
    }
}