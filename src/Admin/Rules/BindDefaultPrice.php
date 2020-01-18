<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class BindDefaultPrice extends AdminRule
{
    //On all events
    public function creating(AdminModel $row)
    {
        //Bind default price into order item with related product
        if ( $model = $row->getProduct() ) {
            $row->default_price = $model->defaultPriceWithoutTax;
        }
    }
}