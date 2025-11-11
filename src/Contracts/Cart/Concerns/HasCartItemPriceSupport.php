<?php

namespace AdminEshop\Contracts\Cart\Concerns;

trait HasCartItemPriceSupport
{
    public function getPrice($priceKey = 'priceWithVat')
    {
        return $this->getIdentifierClass()->getPrice($this, $priceKey);
    }
}