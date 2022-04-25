<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin\Models\RoutesSeo;

trait SeoTrait
{
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        $arrayable = array_keys($this->getArrayableAttributes());

        foreach (['meta_title', 'meta_keywords', 'meta_description'] as $key) {
            if ( in_array($key, $arrayable) ) {
                $attributes[$key] = $this->{$key};
            }
        }

        $metaImages = [];

        //Add meta images
        if ( in_array('meta_image', $arrayable) && $this->meta_image && count($this->meta_image) > 0 ) {
            $metaImages = array_merge($metaImages, $this->meta_image);
        } else if ( $this instanceof RoutesSeo ) {
            $metaImages = array_merge($metaImages, $this->image ?: []);
        }

        $attributes['meta_image'] = array_map(function($item){
            return $item->resize(1200, 630)->url;
        }, $metaImages);

        return $attributes;
    }
}