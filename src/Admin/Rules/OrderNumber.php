<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class OrderNumber extends AdminRule
{
    public $frontend = true;

    /*
     * Firing callback on create row
     */
    public function creating(AdminModel $row)
    {
        if ( config('admineshop.cart.order.number.custom', false) === true ) {
            $row->setOrderNumber();
        }
    }
}