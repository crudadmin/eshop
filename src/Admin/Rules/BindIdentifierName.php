<?php

namespace AdminEshop\Admin\Rules;

use AdminEshop\Contracts\Cart\Identifiers\DefaultIdentifier;
use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class BindIdentifierName extends AdminRule
{
    //On all events
    public function creating(AdminModel $row)
    {
        if ( ! $row->identifier ) {
            $identifier = null;

            //Get identifier from inserted product
            if ( $model = $row->getProduct() ) {
                $identifier = $row->getProduct()->getModelIdentifier();
            }

            $identifier = $identifier ?: config('admineshop.default_identifier', DefaultIdentifier::class);

            $row->identifier = (new $identifier)->getName();
        }
    }
}