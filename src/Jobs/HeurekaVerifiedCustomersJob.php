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

        try {
            $order = $this->order;

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
        } catch (Exception $e){
            Log::channel('store')->error($e);
        }
    }
}
