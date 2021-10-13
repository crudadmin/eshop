<?php

namespace AdminEshop\Listeners;

use Carbon\Carbon;
use Cart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use OrderService;

class UpdateTemporaryStockListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        if ( Cart::isStockBlockEnabled() === false ){
            return;
        }

        Cart::syncBlockedCartItems($event->getCart());
    }
}
