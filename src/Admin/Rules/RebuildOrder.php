<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;
use Admin;
use Ajax;
use Store;

class RebuildOrder extends AdminRule
{
    /*
     * Firing callback on create row
     */
    public function created(AdminModel $row)
    {
        //If is order created via admin, then uncount
        $row->syncStock('-', 'order.new-backend');

        $row->calculatePrices();
    }

    /*
     * Firing callback on update row
     */
    public function updated(AdminModel $row)
    {
        //If order is canceled, then add products back to stock
        if (
            Store::getOrdersStatus($row->status_id)?->return_stock === true
            && Store::getOrdersStatus($row->getOriginal('status_id'))?->return_stock !== true
        ) {
            $row->syncStock('+', 'order.canceled');
        }

        $priceBefore = (float)$row->getOriginal('price_vat');

        //Change delivery prices etc..
        $row->calculatePrices();

        //If order price has been changed on the background,
        //we need notify user about this. Because sometimes bug may happend!
        //We need know about that, especially administrator to findout that something is wrong.
        if ( $priceBefore !== (float)$row->price_vat ) {
            Ajax::warning(
                sprintf(
                    _('Cena objednávky bola po uložení zmenená z <strong>%s</strong> na <strong>%s</strong>.'),
                    Store::priceFormat($priceBefore),
                    Store::priceFormat($row->price_vat),
                )
            );
        }
    }

    /*
     * On delete product from admin, add goods back to stock
     */
    public function deleted($row)
    {
        //If order has been uncounted from stock yet
        if ( Store::getOrdersStatus($row->status_id)?->return_stock !== true ) {
            $row->syncStock('+', 'order.deleted');
        }
    }
}