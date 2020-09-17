<?php

namespace AdminEshop\Contracts\Delivery\Jobs;

use AdminEshop\Contracts\Delivery\ShipmentException;
use AdminEshop\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use OrderService;

class SendShippingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = $this->order;

        OrderService::setOrder($order);

        $deliveryId = $order->delivery_id;

        //If no provider for given delivery method is available
        if ( !($provider = OrderService::getShippingProvider($deliveryId)) ){
            return;
        }

        //Try send shipping, and log output
        try {
            if (!($package = $provider->createPackage())){
                throw new ShipmentException('Doprava nebola zaregistrovaná.');
            }

            //Save error response
            if ( $package->isSuccess() == false ){
                Log::error($package->getData());
            }

            $order->delivery_status = $package->isSuccess() ? 'ok' : 'error';
            $order->delivery_identifier = $package->shippingId();
            $order->addDeliveryMessage($package->getMessage());
            $order->save();
        } catch (ShipmentException $error){
            $order->delivery_status = 'error';
            $order->addDeliveryMessage($error->getMessage());
            $order->save();
        }
    }
}
