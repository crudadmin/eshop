<?php

namespace AdminEshop\Contracts\Cart\Identifiers;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Identifiers\Identifier;
use Discounts;

class DefaultIdentifier extends Identifier
{
    /*
     * Retuns name of identifier
     */
    public function getName()
    {
        return 'default';
    }

    /**
     * Get model by given cart type
     * If this method returns false instead of null
     * item without model will be valid and
     * wont be automatically removed from cart.
     *
     * @param  CartItem  $item
     * @return  Admin\Eloquent\AdminModel|CartItem|null
     */
    public function getItemModel($item, $cache)
    {
        $originalObject = $item->getOriginalObject() ?: $item;

        //If item has set original object when if eloquent identifier is missing.
        //Also original object needs to be also with discountable support
        if ( Discounts::hasDiscountableTrait($originalObject) ) {
            return $originalObject;
        }

        return false;
    }
}

?>