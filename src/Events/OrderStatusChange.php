<?php

namespace AdminEshop\Events;

use AdminEshop\Models\Orders\Order;
use AdminEshop\Models\Orders\OrdersStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChange
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $status;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Order $order, OrdersStatus $status)
    {
        $this->order = $order;
        $this->status = $status;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getStatus()
    {
        return $this->status;
    }
}
