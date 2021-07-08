<?php

namespace AdminEshop\Contracts\Cart\Concerns;

trait HasPriceSupport
{
    public function getPrice($priceKey = 'priceWithVat')
    {
        return $this->getIdentifierClass()->getPrice($this, $priceKey);
    }
}