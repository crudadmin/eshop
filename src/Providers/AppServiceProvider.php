<?php
namespace AdminEshop\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Http\Kernel;
use Admin;

class AppServiceProvider extends ServiceProvider
{
    protected $providers = [
        ConfigServiceProvider::class,
        StoreServiceProvider::class,
        BasketServiceProvider::class,
        RulesServiceProvider::class,
    ];

    protected $facades = [
        'Store' => \AdminEshop\Facades\StoreFacade::class,
        'Basket' => \AdminEshop\Facades\BasketFacade::class,
    ];

    protected $routeMiddleware = [
        'client' => \AdminEshop\Middleware\Authenticate::class,
        'client.guest' => \AdminEshop\Middleware\RedirectIfAuthenticated::class,
        'basket' => \AdminEshop\Middleware\BasketMiddleware::class,
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

        //Load routes
        $this->loadRoutesFrom(__DIR__.'/../Routes/routes.php');
    }

    private function addPublishes()
    {
        $this->publishes([__DIR__ . '/../Views' => resource_path('vendor/admineshop') ], 'admineshop.views');
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