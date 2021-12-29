<?php

namespace AdminEshop\Listeners;

use Admin;
use AdminEshop\Models\Clients\Client;
use AdminEshop\Models\Store\CartToken;
use Cart;

class ClientLoggedInListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        //Allow only on client update
        if ( !($event->user instanceof Client) ) {
            return;
        }

        $client = $event->user;

        if ( ($cartToken = Cart::getDriver()->getCartSession()) instanceof CartToken ){
            $cartToken->update([
                'client_id' => $client->getKey()
            ]);
        }

        //Update favourites
        $clientsFavouritesModel = Admin::getModel('ClientsFavourite');
        $clientsFavouritesModel->activeSession()->update(
            $clientsFavouritesModel->getFavouritesIdentifiers($client)
        );
    }
}
