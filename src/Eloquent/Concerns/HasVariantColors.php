<?php

namespace AdminEshop\Eloquent\Concerns;

use Store;

trait HasVariantColors
{
    public function getColorsAttribute()
    {
        $colorAttribute = env('ATTR_COLOR_EXACT_ID') ?: env('ATTR_COLOR_ID');

        //If relation has not been loaded by developer
        if ( !$this->relationLoaded('attributesItems') ){
            return [];
        }

        $colorAttributes = $this->attributesItems->whereIn('attribute_id', explode(',', $colorAttribute));

        if ( $colorAttributes->count() == 0 ){
            return [];
        }

        return $colorAttributes->map(function($item){
            return $item;
        })->filter()->values()->each->setVisible(['id', 'name', 'color']);
    }

    public function addColorsInResponse()
    {
        $this->makeVisible([
            'colors',
        ]);

        $this->append([
            'colors',
        ]);
    }
}
