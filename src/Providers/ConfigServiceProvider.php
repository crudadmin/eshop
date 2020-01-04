<?php

namespace AdminEshop\Providers;

use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeAdminConfigs();

        $this->mergeMarkdownConfigs();

        $this->turnOfCacheForAdmin();

        $this->pushComponentsPaths();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /*
     * Merge crudadmin config with admineshop config
     */
    private function mergeAdminConfigs($key = 'admin')
    {
        //test 2
        $admineshop_config = require __DIR__.'/../Config/config.php';

        $config = $this->app['config']->get($key, []);

        $this->app['config']->set($key, array_merge($admineshop_config, $config));

        //Merge selected properties with two dimensional array
        foreach (['groups', 'models', 'author', 'passwords', 'gettext_source_paths'] as $property) {
            if ( ! array_key_exists($property, $admineshop_config) || ! array_key_exists($property, $config) )
                continue;

            $attributes = array_merge($admineshop_config[$property], $config[$property]);

            //If is not multidimensional array
            if ( count($attributes) == count($attributes, COUNT_RECURSIVE) )
                $attributes = array_unique($attributes);

            $this->app['config']->set($key . '.' . $property, $attributes);
        }
    }

    /*
     * Update markdown settings
     */
    private function mergeMarkdownConfigs($key = 'mail.markdown')
    {
        $config = $this->app['config']->get($key, []);

        //Update default theme
        if ( $config['theme'] == 'default' ) {
            $config['theme'] = 'admineshop';
        }

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
            if ( admin() )
                $this->app['config']->set('admin.cache_time', 1);
        });
    }
}
