<?php

namespace AdminEshop\Eloquent\Concerns;

/**
 * @see  AdminEshop\Eloquent\Concerns\DiscountHelper for implementing this methods
 * You only need create buildCartItem, if your model is not assigned to cart
 */
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