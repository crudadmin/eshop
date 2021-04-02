<?php

namespace AdminEshop\Events;

use AdminEshop\Models\Products\ProductsStocksLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stockLog;

    public $product;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($product, ProductsStocksLog $stockLog)
    {
        $this->product = $product;
        $this->stockLog = $stockLog;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function getStockLog()
    {
        return $this->stockLog;
    }
}
