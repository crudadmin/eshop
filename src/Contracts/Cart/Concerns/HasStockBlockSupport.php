<?php

namespace AdminEshop\Contracts\Cart\Concerns;

use AdminEshop\Models\Store\CartStockBlock;
use Carbon\Carbon;


trait HasStockBlockSupport
{
    public function isStockBlockEnabled()
    {
        return config('admineshop.stock.temporary_block_time', 0) > 0;
    }

    private function getItemsToBlock($cartItems)
    {
        $block = collect();

        foreach ($cartItems as $item) {
            if ( $item->getIdentifierClass()->hasTemporaryStockBlock() == false ){
                continue;
            }

            $block[] = [
                'where' => $item->getIdentifierClass()->getOrderItemsColumns(),
                'quantity' => $item->getQuantity(),
            ];
        }

        return $block;
    }

    public function getStockBlockIdentifier()
    {
        return [
            'cart_token_id' => $this->getDriver()->getCartSession()->getKey(),
        ];
    }

    public function syncBlockedCartItems($cartItems = null)
    {
        $cartItems = $cartItems ?: $this->all();

        $block = $this->getItemsToBlock($cartItems);

        $cartIdentifier = $this->getStockBlockIdentifier();

        $cartBlockedStock = CartStockBlock::where($cartIdentifier)->get();

        $existingBlocks = collect();

        foreach ($block as $i => $item) {
            $blockedRow = $cartBlockedStock->filter(function($row) use ($item) {
                foreach ($item['where'] as $key => $value) {
                    if ( $row->{$key} != $value ){
                        return false;
                    }
                }

                return true;
            })->first();

            $data = [
                'quantity' => $item['quantity'],
                'blocked_at' => Carbon::now(),
            ];

            //Create new block items
            if ( is_null($blockedRow) ){
                $blockedRow = CartStockBlock::create($item['where'] + $cartIdentifier + $data);
            } else if ( $blockedRow->quantity != $item['quantity'] ){
                $blockedRow->update($data);
            }

            $existingBlocks[] = $blockedRow->getKey();
        }

        $cartBlockedStock->whereNotIn('id', $existingBlocks)->each->forceDelete();
    }
}