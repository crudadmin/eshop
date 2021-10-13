<?php

namespace AdminEshop\Providers;

use AdminEshop\Events\CartUpdated;
use AdminEshop\Events\OrderCreated;
use AdminEshop\Listeners\OrderCreatedListener;
use AdminEshop\Listeners\UpdateTemporaryStockListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventsServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreated::class => [
            OrderCreatedListener::class,
        ],
        CartUpdated::class => [
            UpdateTemporaryStockListener::class,
        ],
    ];
}
