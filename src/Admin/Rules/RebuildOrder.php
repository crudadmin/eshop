<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;
use Admin;
use Ajax;

class RebuildOrder extends AdminRule
{
    /*
     * Firing callback on create row
     */
    public function created(AdminModel $row)
    {
        //If is order created via admin, then uncount
        $row->syncWarehouse('-', 'order.new-backend');

        $row->calculatePrices();
    }

    /*
     * Firing callback on update row
     */
    public function updated(AdminModel $row)
    {
        //If order is canceled, then add products back to warehouse
        if ( $row->status == 'canceled' && $row->getOriginal('status') != 'canceled') {
            $row->syncWarehouse('+', 'order.canceled');
        }

        //Change delivery prices etc..
        $row->calculatePrices();
    }

    /*
     * On delete product from admin, add goods back to warehouse
     */
    public function deleted($row)
    {
        if ( $row->status != 'canceled' ) {
            $row->syncWarehouse('+', 'order.deleted');
        }
    }
}