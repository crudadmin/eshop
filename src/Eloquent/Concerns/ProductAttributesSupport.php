<?php

namespace AdminEshop\Eloquent\Concerns;

interface ProductAttributesSupport
{
    /**
     * Set if given model has allowed attributes support
     *
     * @return  bool
     */
    public function hasAttributesEnabled();
}