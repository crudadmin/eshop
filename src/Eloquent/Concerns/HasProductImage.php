<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\ImageResponse;
use Store;

trait HasProductImage
{
    /**
     * You can overide thumbnail resize width and height
     *
     * @return  array [width, height]
     */
    public function getThumbnailSize()
    {
        return [null, 300];
    }

    public function getImageOrDefaultAttribute()
    {
        return $this->image ? $this->image : Store::getSettings()->default_image;
    }

    public function getThumbnailAttribute()
    {
        if ( !$this->imageOrDefault ){
            return;
        }

        return new ImageResponse(
            $this->imageOrDefault->resize(
                ...$this->getThumbnailSize()
            )
        );
    }
}