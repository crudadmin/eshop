<?php

namespace AdminEshop\Contracts\Cart\Identifiers;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Identifiers\HasIdentifier;
use AdminEshop\Contracts\Cart\Identifiers\Identifier;

class DefaultIdentifier extends Identifier implements HasIdentifier
{
    /*
     * Retuns name of identifier
     */
    public function getName()
    {
        return 'default';
    }

    /**
     * Keys in array are assigned to eloquents tables
     *
     * @return  array
     */
    public static function getIdentifyKeys()
    {
        return [];
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
        return false;
    }
}

?>