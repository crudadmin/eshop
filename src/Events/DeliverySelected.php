<?php

namespace AdminEshop\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DeliverySelected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $delivery;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($delivery)
    {
        $this->delivery = $delivery;
    }

    public function getDelivery()
    {
        return $this->delivery;
    }
}
