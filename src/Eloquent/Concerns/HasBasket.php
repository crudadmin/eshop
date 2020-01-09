<?php

namespace AdminEshop\Eloquent\Concerns;

trait HasBasket
{
    public function scopeBasketSelect($query)
    {
        $query->select(['id', 'price', 'tax_id']);
    }
}