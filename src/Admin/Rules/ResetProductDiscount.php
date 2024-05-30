<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminRule;
use Admin\Eloquent\AdminModel;
use Admin;
use Store;

class ResetProductDiscount extends AdminRule
{
    //On all events
    public function creating(AdminModel $row)
    {
        $this->resetEmptyDiscount($row);
    }

    public function updating(AdminModel $row)
    {
        $this->resetEmptyDiscount($row);
    }

    public function resetEmptyDiscount($row)
    {
        //Reset discount on change to default
        if ( $row->discount_operator == 'default' ) {
            $row->discount = null;
        }
    }
}