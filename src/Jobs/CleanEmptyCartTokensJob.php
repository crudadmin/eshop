<?php

namespace AdminEshop\Jobs;

use Admin;
use AdminEshop\Models\Store\CartStockBlock;
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
        $removeOldStockBlocks = config('admineshop.stock.temporary_block_time', 0) ?: 0;

        Log::channel('store')->info('Cart tokens remover initialized. [inactive '.$removeInactiveAfterDays.' days / empty '.$removeEmptyAfterDays.' days]');

        //Remove older stock blocks than given limit
        if ( is_numeric($removeOldStockBlocks) && $removeOldStockBlocks > 0 ) {
            CartStockBlock::where('blocked_at', '<', Carbon::now()->addMinutes(-$removeOldStockBlocks))->forceDelete();
        }

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
            $this->removeTokens($tokens);

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
            $this->removeTokens($tokens);

            Log::channel('store')->info('Empty cart tokens removed: '.$count);
        }
    }

    private function removeTokens($tokensQuery)
    {
        $tokensToRemove = $tokensQuery->pluck('id')->toArray();

        //Remove tokens favourites first
        Admin::getModel('ClientsFavourite')->whereIn('cart_token_id', $tokensToRemove)->forceDelete();

        $tokensQuery->forceDelete();
    }
}
