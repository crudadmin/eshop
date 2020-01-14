<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminRule;
use Admin\Eloquent\AdminModel;
use Ajax;

class SetDefault extends AdminRule
{
    //On all events
    public function fire(AdminModel $row)
    {
        //If is set default tax, then reset all others taxs as default
        if ( $row->default == true ){
            $row->newQuery()->where('id', '!=', $row->getKey())->update(['default' => 0]);
        }

        //If does not exist default tax
        else if ( $row->newQuery()->where('default', 1)->count() == 0 || $row->getOriginal('default') == 1 ) {
            $row->default = 1;
        }
    }

    /*
     * Firing callback on delete row
     */
    public function delete(AdminModel $row)
    {
        if ( $row->default == true ) {
            Ajax::error(_('Nie je možné vymazať predvolený záznam.'));
        }
    }
}