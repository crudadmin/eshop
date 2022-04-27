<?php

namespace AdminEshop\Contracts\Cart\Identifiers;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Identifiers\DefaultIdentifier;
use Discounts;

class DiscountIdentifier extends DefaultIdentifier
{
    /*
     * Retuns name of identifier
     */
    public function getName()
    {
        return 'discount';
    }

    /**
     * Can be this sum skipped in summary?
     *
     * @return  bool
     */
    public function skipInSummary()
    {
        return true;
    }

    /**
     * We need return false, because this is invalid item
     * Just imaginary discount identifier
     *
     * @param  CartItem  $item
     * @return  Admin\Eloquent\AdminModel|CartItem|null
     * @return  bool
     */
    public function getItemModel($item, $cache)
    {
        return false;
    }
}

?>