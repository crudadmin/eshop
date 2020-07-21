<?php

namespace AdminEshop\Eloquent\Concerns;

interface DiscountSupport
{
    /**
     * Returns cart item by given product
     *
     * @return  AdminEshop\Contracts\CartItem|null
     */
    public function buildCartItem();

    /**
     * Returns cached cart item by given product.
     * In admin, we need return cached instance
     *
     * @return  AdminEshop\Contracts\CartItem|null
     */
    public function getCartItem();
}