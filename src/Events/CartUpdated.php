<?php

namespace AdminEshop\Events;

use AdminEshop\Contracts\Collections\CartCollection;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CartUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $cart;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(CartCollection $cart)
    {
        $this->cart = $cart;
    }

    public function getCart()
    {
        return $this->cart;
    }
}
