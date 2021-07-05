<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\ImageResponse;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsGallery;
use Admin\Helpers\SEO;
use DB;
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
        if ( $image = $this->image ){
            return $image;
        }

        //Default image from actual gallery
        if ( $galleryMainImage = $this->getAttribute('galery_default_image') ) {
            return (new ProductsGallery([
                'image' => $galleryMainImage,
            ]))->image;
        }

        //Return parent product image, if variant does not have set image, but parent product has...
        if ( $mainProductImage = $this->main_image ){
            $this->setRawAttributes([
                'image' => $mainProductImage,
            ]);

            return $this->image;
        }

        //Default gallery image for ProductVariant has higher priority priority than main product image
        if ( $galleryMainImage = $this->getAttribute('galery_main_default_image') ) {
            return (new ProductsGallery([
                'image' => $galleryMainImage,
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

    public function getMetaImageThumbnailAttribute($value)
    {
        $seo = new SEO;
        $seo->setModel($this);

        return collect($seo->getImages())->map(function($image){
            return $image->url;
        });
    }

    public function scopeWithMainGalleryImage($query, $withMainProductInVariants = false)
    {
        $gallerySub = DB::table('products_galleries')
                        ->selectRaw('products_galleries.image, products_galleries.product_id')
                        ->where('products_galleries.default', 1)
                        ->whereNotNull('products_galleries.published_at')
                        ->groupBy('product_id');

        //Select default image from gallery row
        $query->leftJoinSub($gallerySub, 'products_galleries', function($join) {
            $join->on('products_galleries.product_id', '=', 'products.id');
        });
        $query->addSelect('products_galleries.image as galery_default_image');

        //Select default image from parent product gallery row
        if ( $withMainProductInVariants ) {
            $query->leftJoinSub($gallerySub, 'products_main_galleries', function($join) {
                $join->on('products_main_galleries.product_id', '=', 'products.product_id');
            });
            $query->addSelect('products_main_galleries.image as galery_main_default_image');
        }

    }
}