<?php

namespace AdminEshop\Contracts\Cart\Identifiers;

use Admin;
use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Identifiers\Concerns\UsesIdentifier;
use AdminEshop\Contracts\Cart\Identifiers\Identifier;
use AdminEshop\Contracts\Cart\Identifiers\ProductsIdentifier;
use AdminEshop\Models\Products\ProductsVariant;
use Store;

class SubscriptionIdentifier extends ProductsIdentifier
{
    /*
     * Retuns name of identifier
     */
    public function getName()
    {
        return 'subscription';
    }

    public function hasDiscounts()
    {
        return false;
    }

    public function hasTemporaryStockBlock()
    {
        return false;
    }
}

?>