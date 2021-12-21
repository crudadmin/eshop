<?php

namespace AdminEshop\Jobs;

use AdminEshop\Models\Orders\Order;
use Exception;
use Heureka\ShopCertification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Log;
use Throwable;

class HeurekaVerifiedCustomersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;

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
        if ( !($key = config('admineshop.heureka.verified_customers.key')) ){
            return;
        }

        $order = $this->order;

        try {
            $instance = new ShopCertification($key, [
                'service' => ShopCertification::HEUREKA_SK
            ]);

            $instance
                ->setEmail($order->email)
                ->setOrderId((int)$order->number);

            foreach ($order->getVerifiedCustomersItemsIds() as $id) {
                $instance->addProductItemId($id);
            }

            $instance->logOrder();

            Log::channel('store')->info('Heureka verified customers sent successfully for order '.$order->number);
        } catch (Exception $e){
            $order->log()->create([
                'type' => 'error',
                'code' => 'heureka-verified-customers',
                'message' => 'Nepodarilo sa odoslať potvrdenie do služby overené zákaznikmi.',
                'log' => $e->getMessage(),
            ]);

            Log::channel('store')->error($e);
        }
    }
}
