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
    public function getHeurekaListing()
    {
        return $this->withHeurekaListing()->get();
    }

    public function scopeWithHeurekaListing($query)
    {
        // $query->with([]);
    }

    public function toHeurekaArray($parentProduct = null)
    {
        $array = $this->setVisible([
            'id', 'name', 'code', 'ean', 'description',
            'priceWithVat',
        ])->append([
            'priceWithVat',
        ])->setLocalizedResponse()->toArray();

        return [
            'id' => $this->heurekaItemId,
        ] + $array + [
            'manufacturer' => $this->getHeurekaManufacturer($parentProduct),
            'delivery_date' => $this->getHeurekaStock($parentProduct),
            'heureka_item_id' => $parentProduct ? $parentProduct->getKey() : $this->getKey(),
            'heureka_url' => $this->getHeurekaUrl($parentProduct),
            'heureka_thumbnail' => $this->getHeurekaThumbnail($parentProduct),
            'heureka_category_list' => $this->getHeurekaCategoryList($parentProduct),
            'attributes' => $this->getHeurekaAttributes($parentProduct)
        ];
    }

    public function getHeurekaItemIdAttribute()
    {
        return $this->product_id ? $this->product_id.'_'.$this->getKey() : $this->getKey();
    }

    public function getHeurekaStock($parentProduct = null)
    {
        return $this->hasStock ? 0 : null;
    }

    public function getHeurekaManufacturer($parentProduct = null)
    {

    }

    public function getHeurekaUrl()
    {

    }

    public function getHeurekaAttributes()
    {
        if ( ! $this->hasAttributesEnabled() ) {
            return collect();
        }

        return $this->attributesList->map(function($item){
            return [
                'name' => $item->name,
                'value' => $item->items->first()?->name,
            ];
        });
    }

    public function getHeurekaThumbnail()
    {
        if ( $image = $this->thumbnail ) {
            if ( $image instanceof ImageResponse ){
                return $image->x1;
            }

            return (string)$image;
        }
    }

    public function getHeurekaCategoryList($parentProduct = null, $withOriginalName = false)
    {
        $categories = ($parentProduct ?: $this)?->categories;

        if ( !$categories ){
            return [];
        }

        $categories = $categories->each->setLocalizedResponse()->map(function($row) use ($withOriginalName) {
            if ( $withOriginalName === true ){
                return $row->name;
            }

            return $row->heureka_name ?: $row->name;
        })->toArray();

        return array_unique($categories);
    }
}