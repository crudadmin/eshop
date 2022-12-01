<?php

namespace AdminEshop\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Exception;
use AdminEshop\Mail\OrderStatus;
use Illuminate\Support\Facades\Mail;
use Log;
use Store;
use Ajax;

class SendEmailOnOrderStatusChange
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $order = $event->order;
        $status = $event->status;

        //Skip status email
        if ( request()->has('$ignore_status_email') ){
            return;
        }

        if ( $status->email_send === true ){
            try {
                Mail::to($order->email)->send(new OrderStatus($order, $status));

                $order->logReport('info', null, $message = 'Email o zmene stavu objednávky "'.$order->status->name.'" bol odoslaný.');

                Ajax::notice($message);
            } catch (Exception $e){
                $order->logReport('error', null, 'Email o zmene stavu objednávky nebol odoslaný.', $e->getMessage());

                Log::error($e);
            }
        }
    }
}
