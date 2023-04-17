<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Contracts\ImageResponse;
use AdminEshop\Models\Products\Product;
use Store;

trait HasFeed
{
    public function getFeedItemIdAttribute()
    {
        return $this->product_id ? $this->product_id.'_'.$this->getKey() : $this->getKey();
    }

    public function scopeWithFeedListing($query)
    {
        $query->parentProducts();
    }

    public function getFeedUrl($parentProduct = null)
    {
        //...
    }

    public function getFeedThumbnail()
    {
        if ( $image = $this->thumbnail ) {
            if ( $image instanceof ImageResponse ){
                return $image->x2;
            }

            return (string)$image;
        }
    }

    public function getFeedCategoryList($parentProduct = null, $withOriginalName = false, $mutatorField)
    {
        if ( !($categories = ($parentProduct ?: $this)?->getCategoriesTree()[0] ?? null) ){
            return [];
        }

        return collect($categories)->each->setLocalizedResponse()->map(function($row) use ($withOriginalName, $mutatorField) {
            if ( $mutatorField ){
                $name = $row->{$mutatorField} ?: ($withOriginalName ? $row->name : null);
            } else if ( $withOriginalName ) {
                $name = $row->name;
            }

            return $name;
        })->filter()->unique()->toArray();
    }
}