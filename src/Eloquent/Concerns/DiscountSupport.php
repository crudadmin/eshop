<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\CartItem;

interface DiscountSupport
{
    /**
     * Returns cart item by given product
     *
     * @return  AdminEshop\Contracts\CartItem
     */
    public function getCartItem();
}