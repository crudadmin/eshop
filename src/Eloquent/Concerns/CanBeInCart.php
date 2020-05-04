<?php

namespace AdminEshop\Eloquent\Concerns;

interface CanBeInCart
{
    /**
     * Returns cart identifier of actual eloquent
     *
     * @return  string
     */
    public function getModelIdentifier();

    /**
     * Return booted identifier
     *
     * @return  AdminEshop\Contracts\Cart\Identifiers\Identifier
     */
    public function getIdentifier();

    /**
     * Returns cart item by given product
     *
     * @return  AdminEshop\Contracts\CartItem|null
     */
    public function getCartItem();
}