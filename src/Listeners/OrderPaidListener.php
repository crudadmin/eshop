<?php

namespace AdminEshop\Listeners;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderPaidListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $order = $event->getOrder();

        //Update order status paid
        $order->update([
            'paid_at' => Carbon::now()
        ]);

        //Countdown product stock on payment
        if ( config('adminpayments.stock.countdown.on_order_paid', true) == true ) {
            $order->syncStock('-', 'order.paid');
        }
    }
}
