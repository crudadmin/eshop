<?php

namespace AdminEshop\Providers;

use Admin\Providers\AdminHelperServiceProvider;

class ConfigServiceProvider extends AdminHelperServiceProvider
{
    private $storeConfigKey = 'admineshop';

    private function getStoreConfigPath()
    {
        return __DIR__.'/../Config/config.php';
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            $this->getStoreConfigPath(), $this->storeConfigKey
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //Merge crudadmin configs
        $this->mergeAdminConfigs(require __DIR__.'/../Config/admin.php');

        //Merge admineshop configs
        $this->mergeConfigs(
            require $this->getStoreConfigPath(),
            $this->storeConfigKey,
            ['order.codes'],
            [],
        );

        $this->setPaymentConfig();

        $this->mergeMarkdownConfigs();

        $this->turnOfCacheForAdmin();

        $this->pushComponentsPaths();

        $this->addStoreLogChannel();
    }

    private function addStoreLogChannel()
    {
        $this->app['config']->set('logging.channels.store', [
            'driver' => 'single',
            'path' => storage_path('logs/store.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ]);
    }

    private function setPaymentConfig()
    {
        //Clone payment methods into admin payments config
        config()->set('adminpayments.providers', config('adminpayments.providers', []) + config('admineshop.payment_methods.providers', []));
        config()->set('adminpayments.payment_methods', array_merge(config('adminpayments.payment_methods', []), config('admineshop.payment_methods', [])));

        config()->set('adminpayments.invoices.enabled', config('admineshop.invoices', false));
        config()->set('adminpayments.notificaions.paid', config('admineshop.mail.order.paid_notification', true));
    }
}
