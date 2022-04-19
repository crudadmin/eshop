<?php

namespace AdminEshop\Listeners;

use Admin;
use AdminEshop\Events\ClientRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use OrderService;

class RegisterClientOnOrderCreated
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $data = OrderService::getRequestData();

        //User does not asks for registration
        if ( !($data['register_account'] ?? false) ){
            return;
        }

        $model = Admin::getModel('Client');

        //User with given email already exists
        if ( $model->where('email', $data['email'])->count() > 0 ){
            return;
        }

        //Generate client password
        $password = str_random(6);
        $data['password'] = $password;

        //Create client
        $client = $model->create($data);

        //Assign order into registred client
        if ( $order = OrderService::getOrder() ) {
            $order->update([
                'client_id' => $client->getKey(),
            ]);
        }

        event(new ClientRegistered($client, $password));
    }
}
