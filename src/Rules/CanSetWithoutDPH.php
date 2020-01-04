<?php

namespace AdminEshop\Rules;

use Gogol\Admin\Models\Model as AdminModel;
use Admin;
use Ajax;

class CanSetWithoutDPH
{
    /*
     * Firing callback on update row
     */
    public function fire(AdminModel $row)
    {
        if ( $row->price_operator == 'abs' && $row->price == 0 && $row->tax == false )
            return Ajax::error('Nelze nastavit pravidlo pro cenu bez DPH při podmínce s dopravou zdarma.');
    }
}