<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class ProductsPriceLevelsCheck extends AdminRule
{
    /*
     * Firing callback on create row
     */
    public function creating(AdminModel $row)
    {
        $this->check($row);
    }

    public function updating(AdminModel $row)
    {
        $this->check($row);
    }

    public function check($row)
    {
        //TODO: future checks...
    }
}