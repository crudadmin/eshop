<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Contracts\ImageResponse;
use AdminEshop\Models\Products\Product;
use Store;

/**
 * More info at:
 * https://sluzby.heureka.sk/napoveda/xml-feed/
 */
trait HasHeureka
{
    public function scopeWithHeurekaListing($query)
    {
        // $query->with([]);
    }

    public function getHeurekaStock($parentProduct = null)
    {
        return $this->hasStock ? 0 : null;
    }

    public function getHeurekaManufacturer($parentProduct = null)
    {

    }

    public function getHeurekaName($parentProduct = null)
    {
        return $this->heureka_name ?: $this->getValue('name');
    }

    public function getHeurekaAttributes()
    {
        if ( ! $this->hasAttributesEnabled() ) {
            return collect();
        }

        return $this->attributesList->map(function($attribute){
            if ( !($item = $attribute->items->first()) ){
                return;
            }

            return [
                'name' => $attribute->name,
                'value' => $item?->getAttributeItemValue($attribute),
            ];
        })->filter();
    }
}