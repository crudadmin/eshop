<?php

namespace AdminEshop\Admin\Rules;

use AdminEshop\Contracts\Collections\CartCollection;
use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class BindDefaultPrice extends AdminRule
{
    //On all events
    public function creating(AdminModel $row)
    {
        //Bind default price into order item with related product
        if ( $model = $row->getProduct() ) {
            $row->default_price = $model->defaultPriceWithoutVat;
        }
    }

    public function updating(AdminModel $row)
    {
        $previousModel = (clone $row)->forceFill($row->getRawOriginal())->getItemModel();
        $actualModel = $row->getItemModel();

        //If models has been changed, we need reset default price
        if (
            $actualModel && (
                //If previous model does not exists
                is_null($previousModel)
                || $previousModel->getCartItemModelKey() != $actualModel->getCartItemModelKey()
            )
        ) {
            $row->default_price = $actualModel->defaultPriceWithoutVat;
        }
    }
}