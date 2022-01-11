<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminRule;
use Admin\Eloquent\AdminModel;
use Admin;
use Ajax;
use Store;

class AddMissingPrices extends AdminRule
{
    //On all events
    public function fire(AdminModel $row)
    {
        if ( $row->order && Store::getOrdersStatus($row->status_id)?->return_stock == true ) {
            return Ajax::error('Nie je možné upravovať produkty v už zrušenej objednávke.');
        }

        //Set default vat
        if ( ! $row->vat && ($product = $row->getProduct()) && $product->vat_id ) {
            $row->vat = Store::getVatValueById($product->vat_id);
        }

        //Automatically fill product price if is empty
        if ( $row->price !== 0 && ! $row->price && ! $row->price_vat && ($product = $row->getProduct()) ) {
            $row->price = $product->priceWithoutVat;
        }

        //If one price is missing, calculate others... vat/novat
        if ( ! $row->price ) {
            $row->price = Store::roundNumber($row->price_vat / (1 + ($row->vat / 100)));
        }  else if ( ! $row->price_vat ) {
            $row->price_vat = Store::roundNumber($row->price * (1 + ($row->vat / 100)));
        }
    }
}