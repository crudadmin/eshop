<?php

namespace AdminEshop\Rules;

use Admin\Eloquent\AdminModel;
use Admin;
use Ajax;

class SetDefaultPricing
{
    //On all events
    public function fire(AdminModel $row)
    {
        //If is set default variant, then reset all others
        if ( $row->default == true ){
            $row->newQuery()->where('id', '!=', $row->getKey())->update(['default' => 0]);
        }

        //If does not exist default variant
        else if ( $row->newQuery()->where('default', 1)->count() == 0 || $row->getOriginal('default') == 1 ) {
            $row->default = 1;
        }
    }

    /*
     * Firing callback on delete row
     */
    public function delete(AdminModel $row)
    {
        if ( $row->default == true )
            Ajax::error(_('Není možné vymazat výchozí záznam cenník.'));
    }
}