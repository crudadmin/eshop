<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use Store;
use AdminEshop\Models\Products\Product;

/**
 * More info at:
 * https://sluzby.heureka.sk/napoveda/xml-feed/
 */
trait HasHeureka
{
    public function getHeurekaListing()
    {
        return $this->withCategoryResponse()->withHeurekaListing()->get();
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
        ])->toArray();

        return $array + [
            'heureka_item_id' => $parentProduct ? $parentProduct->getKey() : $this->getKey(),
            'heureka_url' => $this->getHeurekaUrl($parentProduct),
            'heureka_thumbnail' => $this->getHeurekaThumbnail($parentProduct),
            'heureka_category_list' => $this->getHeurekaCategoryList($parentProduct),
        ];
    }

    public function getHeurekaUrl()
    {

    }

    public function getHeurekaThumbnail()
    {
        return ($image = $this->thumbnail) ? (string)$image : null;
    }

    public function getHeurekaCategoryList()
    {
        return [];
    }
}