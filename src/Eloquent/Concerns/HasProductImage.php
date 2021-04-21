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

    /**
     * You can overide detail thumbnail resize width and height
     *
     * @return  array [width, height]
     */
    public function getDetailThumbnailSize()
    {
        return [null, 500];
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

    public function getDetailThumbnailAttribute()
    {
        if ( !$this->imageOrDefault ){
            return;
        }

        return new ImageResponse(
            $this->imageOrDefault->resize(
                ...$this->getDetailThumbnailSize()
            )
        );
    }

    /**
     * Check if given class is enabled
     *
     * @param  string|null  $classname
     *
     * @return  bool
     */
    public function hasGalleryEnabled(string $classname = null)
    {
        $classname = $classname ?: get_class($this);

        $enabledClasses = Store::cache('store.enabledGallery', function(){
            return array_map(function($classname){
                return class_basename($classname);
            }, config('admineshop.gallery.eloquents', []));
        });

        //Check if given class has enabled attributes support
        return in_array(class_basename($classname), $enabledClasses);
    }
}