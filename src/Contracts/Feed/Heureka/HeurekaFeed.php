<?php

namespace AdminEshop\Contracts\Feed\Heureka;

use AdminEshop\Contracts\Feed\Feed;
use Admin;

class HeurekaFeed extends Feed
{
    public $contentType = 'application/xml';

    public function getProducts($query)
    {
        return $query->withHeurekaListing();
    }

    public function toFeedArray($productOrVariant, $parentProduct = null)
    {
        $array = parent::toFeedArray($productOrVariant, $parentProduct);

        return array_merge($array, [
            'name' => $productOrVariant->getHeurekaName($parentProduct),
            'manufacturer' => $productOrVariant->getHeurekaManufacturer($parentProduct),
            'delivery_date' => $productOrVariant->getHeurekaStock($parentProduct),
            'heureka_item_id' => $parentProduct ? $parentProduct->getKey() : $productOrVariant->getKey(),
            'heureka_category_list' => $productOrVariant->getFeedCategoryList($parentProduct, false, 'heureka_name'),
            'attributes' => $productOrVariant->getHeurekaAttributes($parentProduct)
        ]);
    }

    public function data()
    {
        $deliveries = Admin::getModel('Delivery')->whereNotNull('heureka_id')->get();

        return view('admineshop::xml.heureka', compact('deliveries') + [
            'items' => $this->getItems(),
        ])->render();
    }
}
?>