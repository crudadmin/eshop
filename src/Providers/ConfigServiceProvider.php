<?php

namespace AdminEshop\Providers;

use Arr;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    private $eshopConfigKey = 'admineshop';

    private function getEshopConfigPath()
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
            $this->getEshopConfigPath(), $this->eshopConfigKey
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
        $this->mergeConfigs(
            'admin',
            __DIR__.'/../Config/admin.php',
            ['groups', 'models', 'components', 'author', 'passwords', 'gettext_source_paths', 'gettext_admin_source_paths', 'styles', 'scripts']
        );

        //Merge admineshop configs
        $this->mergeConfigs(
            $this->eshopConfigKey,
            $this->getEshopConfigPath(),
            ['routes', 'attributes', 'stock', 'delivery', 'discounts', 'cart', 'mail', 'order', 'order.codes']
        );

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

    /*
     * Merge crudadmin config with admineshop config
     */
    private function mergeConfigs($key, $file, $keys)
    {
        //test 2
        $originalConfig = require $file;

        $config = $this->app['config']->get($key, []);

        $this->app['config']->set($key, array_merge($originalConfig, $config));

        //Merge selected properties with two dimensional array
        foreach ($keys as $property) {
            if ( ! Arr::has($originalConfig, $property) || ! Arr::has($config, $property) ) {
                continue;
            }

            $attributes = array_merge(
                Arr::get($originalConfig, $property),
                Arr::get($config, $property)
            );

            $this->app['config']->set($key . '.' . $property, $attributes);
        }
    }

    /*
     * Update markdown settings
     */
    private function mergeMarkdownConfigs($key = 'mail.markdown')
    {
        $config = $this->app['config']->get($key, []);

        //Add themes from admineshop
        $config['paths'] = array_merge($config['paths'], [
            __DIR__ . '/../Views/mail/',
        ]);

        $this->app['config']->set($key, $config);
    }

    /*
     * Add full components path
     */
    private function pushComponentsPaths($key = 'admin.components')
    {
        $config = $this->app['config']->get($key, []);

        //Add themes from admineshop
        $config = array_merge($config, [
            __DIR__ . '/../Admin/Components',
        ]);

        $this->app['config']->set($key, $config);
    }

    /*
     * For logged administrator turn of eshop/web cache
     */
    private function turnOfCacheForAdmin()
    {
        view()->composer('*', function ($view) {
            if ( admin() ) {
                $this->app['config']->set('admin.cache_time', 1);
            }
        });
    }
}
