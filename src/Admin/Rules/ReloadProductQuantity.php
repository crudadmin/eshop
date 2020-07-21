<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;
use Ajax;

class ReloadProductQuantity extends AdminRule
{
    public function creating(AdminModel $row)
    {
        if ( $order = $row->order ) {
            $this->checkQuantity($row, $row->quantity, 'item.add');
        }
    }

    public function updating(AdminModel $row)
    {
        if ( $order = $row->order ) {
            //We also need update quantity when product will be changed
            if ( $this->hasBeenChangedProduct($row) ) {
                $previousModel = $this->getPreviousRowItemModel($row);

                //We need subtract quantity to new item
                $this->checkQuantity($row, $row->quantity, 'item.changed.new');

                //We need commit previousModel quantity change
                $previousModel->commitWarehouseChange('+', $row->quantity, $row->order_id, 'item.changed.old');
            }

            //Update existing model item
            else {
                $this->checkQuantity($row, $row->quantity - $row->getOriginal('quantity'), 'item.update');
            }
        }
    }

    private function getPreviousRowItemModel($row)
    {
        return (clone $row)->forceFill($row->getRawOriginal())->getItemModel();
    }

    private function hasBeenChangedProduct(AdminModel $row)
    {
        $previousModel = $this->getPreviousRowItemModel($row);
        $actualModel = $row->getItemModel();

        return ($previousModel && $actualModel)
                && $previousModel->getCartItemModelKey() != $actualModel->getCartItemModelKey();
    }

    public function deleting(AdminModel $row)
    {
        if ( $order = $row->order ) {
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