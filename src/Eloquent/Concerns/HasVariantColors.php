<?php

namespace AdminEshop\Eloquent\Concerns;

use Store;

trait HasVariantColors
{
    public function getColorsAttribute()
    {
        $colorAttributes = $this->attributesItems->where('attribute_id', env('ATTR_COLOR_ID'));

        if ( $colorAttributes->count() == 0 ){
            return [];
        }

        return $colorAttributes->map(function($item){
            return Store::getAttributeItem($item->attributes_item_id);
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
