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
        //Send shipping
        OrderService::sendShipping();

        //Generate default invoice document
        $proform = OrderService::makeInvoice('proform');

        //Send email to client
        OrderService::sentClientEmail($proform);

        //Sent store email
        OrderService::sentStoreEmail();

        //Set created order id into cart
        Cart::getDriver()->set('order_id', OrderService::getOrder()->getKey());
    }
}
