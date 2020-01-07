<?php

namespace AdminEshop\Eloquent\Concerns;

use Store;

trait HasProductImage
{
    public function getImageOrDefaultAttribute()
    {
        return $this->image ? $this->image : Store::getSettings()->default_image;
    }
}