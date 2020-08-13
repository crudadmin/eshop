<?php

namespace AdminEshop\Eloquent\Concerns;

interface HasAttributesSupport
{
    /**
     * Check if given class is enabled
     *
     * @param  string|null  $classname
     *
     * @return  bool
     */
    public function hasAttributesEnabled(string $classname = null);
}