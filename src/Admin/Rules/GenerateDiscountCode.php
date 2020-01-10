<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class GenerateDiscountCode extends AdminRule
{
    public function fire(AdminModel $row)
    {
        //Add default code
        if ( ! $row->code ) {
            $row->code = strtoupper(str_random(10));
        }
    }
}