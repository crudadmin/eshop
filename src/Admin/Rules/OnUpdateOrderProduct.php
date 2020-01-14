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

        //Set product prices
        if ( ! $row->price )
            $row->price = Store::roundNumber($row->price_tax / (1 + ($row->tax / 100)));
        else if ( ! $row->price_tax )
            $row->price_tax = Store::roundNumber($row->price * (1 + ($row->tax / 100)));
    }
}