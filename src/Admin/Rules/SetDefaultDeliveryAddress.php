<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class SetDefaultDeliveryAddress extends AdminRule
{
    //On all events
    public function fire(AdminModel $row)
    {
        //If is set default delivery, then reset all others
        if ( $row->default == true ){
            $row->newQuery()->where('type', $row->type)->where('id', '!=', $row->getKey())->update(['default' => 0]);
        }
    }
}