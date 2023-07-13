<?php

namespace AdminEshop\Jobs;

use AdminEshop\Models\Orders\OrdersStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Admin;
use Carbon\Carbon;
use Store;

class SetOrderStatusAfterInactivness implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Store::log()->info('Checking inactive order statuses.');

        $statusesToCheck = OrdersStatus::where('activness_change', 1)
                                ->whereNotNull('activness_status_id')
                                ->get();

        foreach ($statusesToCheck as $status) {
            $passed = Carbon::now()->addDays(-$status->activness_duration);

            $orders = Admin::getModel('Order')
                        ->selectRaw('orders.*')
                        ->where('status_id', $status->getKey())
                        ->whereDoesntHave('logs', function($query) use ($passed) {
                            $query->where('code', '=', 'status')
                                  ->where('created_at', '>', $passed);
                        })
                        ->whereHas('logs', function($query) use ($passed) {
                            $query->where('code', '=', 'status');
                        })
                        ->get();

            if ( count($orders) ){
                Store::log()->info('Found inactive order statuses for status '.$status->name.', orders: '.$orders->pluck('number')->join(',').'.');
            }

            foreach ($orders as $order) {
                $order->update([
                    'status_id' => $status->activness_status_id,
                ]);
            }
        }

    }
}
