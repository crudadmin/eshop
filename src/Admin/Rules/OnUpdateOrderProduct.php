<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminRule;
use Admin\Eloquent\AdminModel;
use Admin;
use Ajax;
use Store;

class OnUpdateOrderProduct extends AdminRule
{
    //On all events
    public function fire(AdminModel $row)
    {
        if ( $row->order && $row->order->status == 'canceled' ) {
            return Ajax::error('Nie je možné upravovať produkty v už zrušenej objednávke.');
        }

        //Set default tax
        if ( ! $row->tax && ($product = $row->getProduct()) && $product->tax_id ) {
            $row->tax = Store::getTaxValueById($product->tax_id);
        }

        //Automatically fill product price if is empty
        if ( $row->price !== 0  && ! $row->price && ! $row->price_tax ) {
            $row->price = $row->getProduct()->priceWithoutTax;
        }

        if ( ! $row->price )
            $row->price = Store::roundNumber($row->price_tax / (1 + ($row->tax / 100)));
        else if ( ! $row->price_tax )
            $row->price_tax = Store::roundNumber($row->price * (1 + ($row->tax / 100)));
    }

    /*
     * Firing callback on update row
     */
    public function create(AdminModel $row)
    {
        //Add change of items into quantity
        if ( $row->order ) {
            $this->checkQuantity($row, $row->quantity);
        }
    }

    /*
     * Firing callback on update row
     */
    public function update(AdminModel $row)
    {
        //Add change of items into quantity
        if ( $row->order ) {
            $this->checkQuantity($row, $row->quantity - $row->getOriginal('quantity'));
        }
    }

    /*
     * Firing callback on delete row
     */
    public function delete(AdminModel $row)
    {
        //Add into quantity
        if ( $row->order ) {
            $this->checkQuantity($row, -$row->quantity);
        }
    }

    private function checkQuantity($row, $qty)
    {
        //If order item has related product
        if ( ! ($product = $row->getProduct()) ){
            return;
        }

        //If in product does not matter about warehouse stock
        if ( $row->product->canOrderEverytime()  )
            return;

        $available = ($row->getProduct()->warehouse_quantity -= $qty);

        if ( $available < 0 ) {
            return Ajax::error('Nie je dostatočný produktu produktov na sklade, pre pridanie daného produktu do objednávky.');
        }

        $row->getProduct()->save();
    }
}