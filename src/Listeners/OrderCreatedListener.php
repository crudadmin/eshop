<?php

namespace AdminEshop\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use OrderService;
use Cart;

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
    }
}
