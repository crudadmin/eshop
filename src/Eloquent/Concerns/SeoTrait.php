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

        $metaImages = collect();

        //Add meta images
        if ( in_array('meta_image', $arrayable) && $this->meta_image && count($this->meta_image) > 0 ) {
            $metaImages = $metaImages->merge($this->meta_image);
        } else if ( $this instanceof RoutesSeo ) {
            $metaImages = $metaImages->merge($this->image ?: []);
        } else if ( $this->getField('image') && $this->image ){
            $metaImages = $metaImages->merge([$this->image]);
        }

        $attributes['meta_image'] = $metaImages->map(function($item){
            return $item->resize(1200, 630)->url;
        })->toArray();

        return $attributes;
    }

    public function setMetaResponse()
    {
        return $this->makeVisible([
            'meta_title',
            'meta_keywords',
            'meta_description',
            'meta_image',
        ]);
    }
}