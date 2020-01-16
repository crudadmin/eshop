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
}

?>