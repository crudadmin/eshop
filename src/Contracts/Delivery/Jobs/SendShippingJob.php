<?php

namespace AdminEshop\Contracts\Delivery\Jobs;

use AdminEshop\Contracts\Delivery\CreatePackageException;
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
    private $options;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order, $options = [])
    {
        $this->order = $order;
        $this->options = $options ?: [];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = $this->order;

        //If no provider for given delivery method is available
        if ( !($provider = $order->getShippingProvider()) || $provider->isActive() == false ){
            return;
        }

        $provider->setOptions(
            $provider->getOptions() + $this->options
        );

        //Try send shipping, and log output
        try {
            if (!($package = $provider->createPackage())){
                throw new ShipmentException('Doprava nebola zaregistrovaná.');
            }

            $order->delivery_status = 'ok';
            $order->delivery_identifier = $package->shippingId();

            if ( $package->getMessage() ) {
                $order->log()->create([
                    'type' => 'info',
                    'code' => 'delivery-info',
                    'message' => $package->getMessage(),
                ]);
            }
        }

        catch (CreatePackageException $error){
            if ( $response = $error->getResponse() ) {
                Log::channel('store')->error($response);
            }

            $order->delivery_status = 'error';

            $order->log()->create([
                'type' => 'error',
                'code' => 'delivery-error',
                'message' => $error->getMessage(),
                'log' => $error->getResponse(),
            ]);
        }

        catch (ShipmentException $error){
            $order->delivery_status = 'error';

            $order->log()->create([
                'type' => 'error',
                'message' => implode(' ', array_wrap($error->getMessage())),
            ]);
        }

        $order->save();
    }
}
