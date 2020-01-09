<?php

namespace AdminEshop\Eloquent\Concerns;

trait HasBasket
{
    public function scopeBasketSelect($query)
    {
        $query->select($this->fixAmbiguousColumn($this->basketSelect ?: []));
    }

    public function addBasketSelect(array $columns = [])
    {
        $this->basketSelect = array_merge($this->basketSelect ?: [], $columns);
    }
}