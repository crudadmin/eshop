<?php

namespace AdminEshop\Providers;

use Admin;
use AdminEshop\Commands\ImportPickupPoints;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $providers = [
        ConfigServiceProvider::class,
        StoreServiceProvider::class,
        RulesServiceProvider::class,
        RouteServiceProvider::class,
    ];

    protected $facades = [
        'Cart' => \AdminEshop\Facades\CartFacade::class,
        'CartDriver' => \AdminEshop\Facades\CartDriverFacade::class,
        'Store' => \AdminEshop\Facades\StoreFacade::class,
        'Discounts' => \AdminEshop\Facades\Discounts::class,
        'OrderService' => \AdminEshop\Facades\OrderServiceFacade::class,
    ];

    protected $routeMiddleware = [
        'client' => \AdminEshop\Middleware\Authenticate::class,
        'client.guest' => \AdminEshop\Middleware\RedirectIfAuthenticated::class,
        'cart' => \AdminEshop\Middleware\CartMiddleware::class,
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Admin::registerAdminModels(__dir__ . '/../Models/**', 'AdminEshop\Models');

        //Boot providers after this provider boot
        $this->bootProviders([
            ViewServiceProvider::class,
            ClientAuthServiceProvider::class,
        ]);

        Carbon::setLocale(config('admin.locale', 'sk'));

        $this->commands([
            ImportPickupPoints::class,
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->bootFacades();

        $this->bootProviders();

        $this->bootRouteMiddleware();

        $this->addPublishes();
    }

    private function addPublishes()
    {
        $this->publishes([__DIR__ . '/../Views' => resource_path('vendor/admineshop') ], 'admineshop.views');
        $this->publishes([__DIR__ . '/../Config/config.php' => config_path('admineshop.php') ], 'admineshop.config');
    }

    public function bootFacades()
    {
        $this->app->booting(function()
        {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();

            foreach ($this->facades as $alias => $facade)
            {
                $loader->alias($alias, $facade);
            }

        });
    }

    public function bootProviders($providers = null)
    {
        foreach ($providers ?: $this->providers as $provider)
        {
            app()->register($provider);
        }
    }

    public function bootRouteMiddleware()
    {
        foreach ($this->routeMiddleware as $name => $middleware)
        {
            $router = $this->app['router'];

            $router->aliasMiddleware($name, $middleware);
        }
    }
}