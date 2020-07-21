<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\CartItem;

trait DiscountHelper
{
    /**
     * Cartitem of given model eloquent
     *
     * @var  null
     */
    protected $cartItem = null;

    /**
     * Set cartItem
     *
     * @param  CartItem  $cartItem
     *
     */
    public function setCartItem(CartItem $cartItem)
    {
        $this->cartItem = $cartItem;

        return $this;
    }

    /**
     * Returns cached cart item by given product.
     * In admin, we need return cached instance
     *
     * @return  AdminEshop\Contracts\CartItem|null
     */
    public function getCartItem()
    {
        //Return cached cartItem
        if ( $this->cartItem ) {
            return $this->cartItem;
        }

        return $this->cartItem = $this->buildCartItem();
    }
}