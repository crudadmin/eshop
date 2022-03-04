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

        $array = [
            'id' => $this->heurekaItemId,
            'name' => $this->getHeurekaName($parentProduct),
        ] + $array + [
            'vat' => Store::getVatValueById($this->vat_id),
            'manufacturer' => $this->getHeurekaManufacturer($parentProduct),
            'delivery_date' => $this->getHeurekaStock($parentProduct),
            'heureka_item_id' => $parentProduct ? $parentProduct->getKey() : $this->getKey(),
            'heureka_url' => $this->getHeurekaUrl($parentProduct),
            'heureka_thumbnail' => $this->getHeurekaThumbnail($parentProduct),
            'heureka_category_list' => $this->getHeurekaCategoryList($parentProduct),
            'attributes' => $this->getHeurekaAttributes($parentProduct)
        ];

        foreach ($array as $key => $value) {
            $array[$key] = is_string($value) ? trim($value) : $value;
        }


        return $array;
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

    public function getHeurekaName($parentProduct = null)
    {
        return $this->heureka_name ?: $this->getValue('name');
    }

    public function getHeurekaUrl()
    {

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

    public function getHeurekaThumbnail()
    {
        if ( $image = $this->thumbnail ) {
            if ( $image instanceof ImageResponse ){
                return $image->x2;
            }

            return (string)$image;
        }
    }

    public function getHeurekaCategoryList($parentProduct = null, $withOriginalName = false)
    {
        if ( !($categories = ($parentProduct ?: $this)?->getCategoriesTree()[0] ?? null) ){
            return [];
        }

        $categoryFullName = null;

        $categories = collect($categories)->each->setLocalizedResponse()->map(function($row) use ($withOriginalName, $categoryFullName) {
            if ( $withOriginalName === true ){
                $name = $row->name;
            } else {
                $name = $row->heureka_name ?: $row->name;
            }

            if ( $row->heureka_full_name ){
                $categoryFullName = $name;
            }

            return $name;
        })->toArray();

        return $categoryFullName ? [$categoryFullName] : array_unique($categories);
    }
}