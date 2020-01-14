<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;
use Ajax;

class ReloadProductQuantity  extends AdminRule
{
    public function create($row)
    {
        if ( $order = $row->order ) {
            $order->calculatePrices();

            $this->checkQuantity($row, $row->quantity, 'item.add');
        }
    }

    public function update($row)
    {
        if ( $order = $row->order ) {
            $order->calculatePrices();

            $this->checkQuantity($row, $row->quantity - $row->getOriginal('quantity'), 'item.update');
        }
    }

    public function delete($row)
    {
        if ( $order = $row->order ) {
            $order->calculatePrices();

            $this->checkQuantity($row, -$row->quantity, 'item.remove');
        }
    }

    /**
     * Check if product quantity can be changed
     *
     * @param  Admin\Eloquent\AdminModel  $row
     * @param  int  $quantity
     * @param  string  $message
     * @return void
     */

    private function checkQuantity($row, int $quantity, $message)
    {
        //If order item does have related product
        //Or if product can be ordered in any time
        if ( !($product = $row->getProduct()) ){
            return;
        }

        //If is no enought items on stock to subtract from product quantity
        if ( $row->product->canOrderEverytime() == false && ($product->warehouse_quantity - $quantity) < 0 ) {
            return Ajax::error('Pre pridanie daného produktu do objednávky <strong>nie je</strong> dostatočný počet produktov na sklade.');
        }

        $product->commitWarehouseChange('-', $quantity, $row->order_id, $message);
    }
}