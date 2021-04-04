<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\ImageResponse;
use AdminEshop\Models\Products\Product;
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
        if ( $this->image ){
            return $this->image;
        }

        //Return parent product image, if variant does not have set image, but parent product has...
        if ( $this->product_image ){
            return (new Product([
                'image' => $this->product_image,
            ]))->image;
        }

        return Store::getSettings()->default_image;
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