<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class SetSearchIndexRule extends AdminRule
{
    public $frontend = true;

    /*
     * Firing callback on create row
     */
    public function creating(AdminModel $row)
    {
        $row->setSearchIndex($row);
    }

    /*
     * Firing callback on update row
     */
    public function updating(AdminModel $row)
    {
        $row->setSearchIndex($row);
    }
}