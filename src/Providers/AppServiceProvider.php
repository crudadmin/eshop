<?php

namespace AdminEshop\Providers;

use Admin;
use Admin\Providers\AdminHelperServiceProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Kernel;

class AppServiceProvider extends AdminHelperServiceProvider
{
    protected $providers = [
        ConfigServiceProvider::class,
        StoreServiceProvider::class,
        RulesServiceProvider::class,
        RouteServiceProvider::class,
        EventsServiceProvider::class,
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
        'store' => \AdminEshop\Middleware\StoreMiddleware::class,
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerModels();

        //Boot providers after this provider boot
        $this->registerProviders([
            ViewServiceProvider::class,
            ClientAuthServiceProvider::class,
        ]);

        Carbon::setLocale(config('admin.locale', 'sk'));

        $this->commands([
            \AdminEshop\Commands\ImportPickupPoints::class,
            \AdminEshop\Commands\CleanEmptyCartTokens::class,
            \AdminEshop\Commands\StockNotification::class,
            \AdminEshop\Commands\MigrateProductAttributesFromVersion2::class,
            \AdminEshop\Commands\RegenerateSearchIndexes::class,
            \AdminEshop\Commands\FixProductCategoriesTree::class,
            \AdminEshop\Commands\CleanDeletedProducts::class,
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerFacades();

        $this->registerProviders();

        $this->bootRouteMiddleware();

        $this->addPublishes();
    }

    private function registerModels()
    {
        if ( config('admineshop.categories.enabled', false) === true ) {
            Admin::registerAdminModels(__dir__ . '/../Models/Category/**', 'AdminEshop\Models\Category');
        }

        if ( count(config('admineshop.import', [])) > 0 ) {
            Admin::registerAdminModels(__dir__ . '/../Models/Import/**', 'AdminEshop\Models\Import');
        }

        Admin::registerAdminModels(__dir__ . '/../Models/Attribute/**', 'AdminEshop\Models\Attribute');
        Admin::registerAdminModels(__dir__ . '/../Models/Clients/**', 'AdminEshop\Models\Clients');

        if ( config('admineshop.delivery.enabled', true) ) {
            Admin::registerAdminModels(__dir__ . '/../Models/Delivery/**', 'AdminEshop\Models\Delivery');
        }

        Admin::registerAdminModels(__dir__ . '/../Models/Invoice/**', 'AdminEshop\Models\Invoice');
        Admin::registerAdminModels(__dir__ . '/../Models/Orders/**', 'AdminEshop\Models\Orders');
        Admin::registerAdminModels(__dir__ . '/../Models/Products/**', 'AdminEshop\Models\Products');
        Admin::registerAdminModels(__dir__ . '/../Models/Store/**', 'AdminEshop\Models\Store');
    }

    private function addPublishes()
    {
        $this->publishes([__DIR__ . '/../Views' => resource_path('views/vendor/admineshop') ], 'admineshop.views');
        $this->publishes([__DIR__ . '/../Config/config.php' => config_path('admineshop.php') ], 'admineshop.config');
        $this->publishes([__DIR__ . '/../Resources/dist' => public_path('vendor/admineshop') ], 'admineshop.resources');
    }
}