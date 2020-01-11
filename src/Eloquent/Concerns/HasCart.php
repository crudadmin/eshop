<?php

namespace AdminEshop\Eloquent\Concerns;

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
}