<?php

namespace AdminEshop\Providers;

use AdminEshop\Events\CartUpdated;
use AdminEshop\Events\OrderCreated;
use AdminEshop\Events\OrderStatusChange;
use AdminEshop\Listeners\ClientLoggedInListener;
use AdminEshop\Listeners\OnAdminUpdateListener;
use AdminEshop\Listeners\OrderCreatedListener;
use AdminEshop\Listeners\SendEmailOnOrderStatusChange;
use AdminEshop\Listeners\UpdateTemporaryStockListener;
use Admin\Resources\Events\OnAdminUpdate;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventsServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreated::class => [
            OrderCreatedListener::class,
        ],
        OrderStatusChange::class => [
            SendEmailOnOrderStatusChange::class,
        ],
        CartUpdated::class => [
            UpdateTemporaryStockListener::class,
        ],
        Login::class => [
            ClientLoggedInListener::class,
        ],
        OnAdminUpdate::class => [
            OnAdminUpdateListener::class,
        ],
    ];
}
