<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\Cart\Identifiers\ProductsIdentifier;
use Cart;

trait HasCart
{
    public function scopeCartSelect($query)
    {
        $query->select($this->fixAmbiguousColumn($this->cartSelect ?: []));
    }

    public function addCartSelect(array $columns = [])
    {
        $this->cartSelect = array_merge($this->cartSelect ?: [], $columns);
    }

    /**
     * Returns cart identifier of actual eloquent
     *
     * @return  string
     */
    public function getModelIdentifier()
    {
        return ProductsIdentifier::class;
    }

    /**
     * Get initialized model identifier
     *
     * @return  AdminEshop\Contracts\Cart\Identifiers\Identifier
     */
    public function getIdentifier()
    {
        $identifierClass = $this->getModelIdentifier();

        return (new $identifierClass)->bootFromModel($this);
    }

    /**
     * Returns cart item
     *
     * @return  AdminEshop\Contracts\CartItema
     */
    public function getCartItem()
    {
        $identifier = $this->getIdentifier();

        return Cart::getItem($identifier);
    }
}