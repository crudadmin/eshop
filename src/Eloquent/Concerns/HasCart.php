<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\Cart\Identifiers\ProductsIdentifier;

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
}