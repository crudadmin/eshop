<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminRule;
use Admin\Eloquent\AdminModel;

class SetDefaultGalleryImage extends AdminRule
{
    //On all events
    public function fire(AdminModel $row)
    {
        $query = $row->newQuery()->where('product_id', $row->product_id);

        //If is set default vat, then reset all others vats as default
        if ( $row->default == true ){
            $query->where('id', '!=', $row->getKey())->update(['default' => 0]);
        }

        //If does not exist default vat
        else if ( $query->where('default', 1)->count() == 0 || $row->getOriginal('default') == 1 ) {
            $row->default = 1;
        }
    }
}