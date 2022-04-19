<?php

namespace AdminEshop\Events;

use AdminEshop\Models\Clients\Client;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $client;
    public $password;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Client $client, $password = null)
    {
        $this->client = $client;
        $this->password = $password;
    }
}
