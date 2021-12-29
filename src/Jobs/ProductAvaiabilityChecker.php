<?php

namespace AdminEshop\Jobs;

use App\Notifications\ProductAvailableNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Admin;
use Log;

class ProductAvaiabilityChecker implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Admin::start();

        $notifications = Admin::getModel('ProductsNotification')
                                ->where('notified', 0)
                                ->select('id', 'product_id', 'products_variant_id', 'email', 'notified_error')
                                ->where(function($query){
                                    $query
                                        ->whereHas('product', function($query){
                                            $query->where('stock_quantity', '>', 0);
                                        })
                                        ->orWhereHas('variant', function($query){
                                            $query->where('stock_quantity', '>', 0);
                                        });
                                })
                                ->with([
                                    'product',
                                    'variant',
                                ])
                                ->get();
        $errored = 0;

        foreach ($notifications as $notification) {
            try {
                $notification->update([ 'notified' => 1 ]);

                $notification->sendNotification();
            } catch (Exception $e){
                $errored++;

                $notification->update([ 'notified_error' => 1 ]);

                Log::error($e);
            }
        }

        Log::channel('store')->info('Products notification check. Total '.$notifications->count().', error: '.$errored.', duration '.round(Admin::end(), 2).'s');
    }
}
