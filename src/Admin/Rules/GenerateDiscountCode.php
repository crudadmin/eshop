<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class GenerateDiscountCode extends AdminRule
{
    public function creating(AdminModel $row)
    {
        $this->generateMissingCode($row);
    }

    public function updating(AdminModel $row)
    {
        $this->generateMissingCode($row);
    }

    public function generateMissingCode($row)
    {
        //Add default code
        if ( ! $row->code ) {
            $row->code = strtoupper(str_random(10));
        }
    }
}