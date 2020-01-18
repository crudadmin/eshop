<?php

namespace AdminEshop\Eloquent\Concerns;

interface CanBeInCart
{
    /**
     * Returns cart identifier of actual eloquent
     *
     * @return  string
     */
    public function getModelIdentifier();
}