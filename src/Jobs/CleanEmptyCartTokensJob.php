<?php

namespace AdminEshop\Jobs;

use Admin;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Log;

class CleanEmptyCartTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $removeInactiveAfterDays = config('admineshop.cart.token.remove_inactive_after_days', 0) ?: 0;
        $removeEmptyAfterDays = config('admineshop.cart.token.remove_empty_after_days', 0) ?: 0;

        Log::channel('store')->info('Cart tokens remover initialized. [inactive '.$removeInactiveAfterDays.' days / empty '.$removeEmptyAfterDays.' days]');

        if ( is_numeric($removeInactiveAfterDays) && $removeInactiveAfterDays > 0 ){
            $this->removeInactiveTokens($removeInactiveAfterDays);
        }

        if ( is_numeric($removeEmptyAfterDays) && $removeEmptyAfterDays > 0 ){
            $this->removeEmptyTokens($removeEmptyAfterDays);
        }
    }

    private function removeInactiveTokens($removeAfterDays)
    {
        $oldAtLeastDays = Carbon::now()->addDays($removeAfterDays * -1);

        $tokens = Admin::getModel('CartToken')
            //Only cart token without no activity for x days
            ->whereDate('cart_tokens.updated_at', '<=', $oldAtLeastDays);

        if ( ($count = $tokens->count()) > 0 ) {
            $tokens->forceDelete();

            Log::channel('store')->info('Inactive cart tokens removed: '.$count);
        }
    }

    private function removeEmptyTokens($removeAfterDays)
    {
        $oldAtLeastDays = Carbon::now()->addDays($removeAfterDays * -1);

        $tokens = Admin::getModel('CartToken')
            //Only cart token without no activity for x days
            ->whereDate('cart_tokens.updated_at', '<=', $oldAtLeastDays)
            ->where(function($query){
                $query
                    ->whereJsonLength('cart_tokens.data->Cart->items', '=', 0)
                    ->orWhereNull('cart_tokens.data->Cart->items');
            });

        if ( ($count = $tokens->count()) > 0 ) {
            $tokens->forceDelete();

            Log::channel('store')->info('Empty cart tokens removed: '.$count);
        }
    }
}
