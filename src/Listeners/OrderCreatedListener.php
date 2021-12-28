<?php

namespace AdminEshop\Listeners;

use AdminEshop\Jobs\HeurekaVerifiedCustomersJob;
use Cart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use OrderService;

class OrderCreatedListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        //Set created order id into cart
        Cart::getDriver()->set('order_id', OrderService::getOrder()->getKey());

        if ( config('admineshop.heureka.verified_customers.enabled', false) === true ) {
            dispatch(new HeurekaVerifiedCustomersJob($event->getOrder()));
        }
    }
}
