<?php

namespace AdminEshop\Rules;

use Admin\Eloquent\AdminModel;
use Admin;
use Ajax;

class OnUpdateOrderProduct
{
    //On all events
    public function fire(AdminModel $row)
    {
        if ( $row->order->status == 'canceled' )
            return Ajax::error('Nelze upravovat produkty v již zrušené objednávce.');

        //Set default tax
        if ( ! $row->tax && $row->product && $row->product->tax )
            $row->tax = $row->product->tax->tax;

        //Automatically variant text by variant
        if ($row->variant_id)
            $row->variant_text = $row->variant->getVariantText();

        //Automatically fill product price if is empty
        if ( $row->price !== 0  && ! $row->price && ! $row->price_tax )
            $row->price = $row->product->setProductVariant( $row->variant )->priceWithoutTax;

        if ( ! $row->price )
            $row->price = $row->price_tax / (1 + ($row->tax / 100));
        else if ( ! $row->price_tax )
            $row->price_tax = $row->price * (1 + ($row->tax / 100));
    }

    /*
     * Firing callback on update row
     */
    public function create(AdminModel $row)
    {
        //Add change of items into quantity
        $this->checkQuantity($row, $row->quantity);
    }

    /*
     * Firing callback on update row
     */
    public function update(AdminModel $row)
    {
        //Add change of items into quantity
        $this->checkQuantity($row, $row->quantity - $row->getOriginal('quantity'));
    }

    /*
     * Firing callback on delete row
     */
    public function delete(AdminModel $row)
    {
        //Add into quantity
        $this->checkQuantity($row, -$row->quantity);
    }

    private function checkQuantity($row, $qty)
    {
        //If order item has related product
        if ( ! ($product = $row->getProduct()) )
            return;

        //If in product does not matter about warehouse stock
        if ( $row->product->canOrderEverytime()  )
            return;

        $available = ($row->getProduct()->warehouse_quantity -= $qty);

        if ( $available < 0 )
            return Ajax::error('Není dostatečný počet produktů na skladě pro přidání daného množství do objednávky.');

        $row->getProduct()->save();
    }
}