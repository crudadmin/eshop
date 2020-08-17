<?php

namespace AdminEshop\Contracts\Cart\Identifiers\Concerns;

interface UsesIdentifier
{
    /**
     * Returns identifier class
     *
     * @return  Identifier
     */
    public function getIdentifierClass();

    /**
     * Returns array of available prices in cartItem
     *
     * @return  array
     */
    public function getPricesArray($discounts = null);

    /**
     * Returns original object
     *
     * @return  mixed
     */
    public function getOriginalObject();
}

?>