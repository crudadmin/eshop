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

    /**
     * Returns all product name information in array
     *
     * @return  array
     */
    public function getProductNameParts() : array;

    /**
     * Returns product name with all additional informations divided with string separator
     *
     * @return  string
     */
    public function getProductName();

    /**
     * Returns identifier key
     */
    public function getKey();
}

?>