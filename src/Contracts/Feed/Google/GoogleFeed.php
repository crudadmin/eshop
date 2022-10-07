<?php

namespace AdminEshop\Contracts\Feed\Google;

use AdminEshop\Contracts\Feed\Feed;
use Admin;

class GoogleFeed extends Feed
{
    public $contentType = 'application/xml';

    public function toFeedArray($productOrVariant, $parentProduct = null)
    {
        $array = parent::toFeedArray($productOrVariant, $parentProduct);

        return array_merge($array, [
            'availability' => $productOrVariant->hasStock ? 'in_stock' : 'out_of_stock',
            'google_product_category' => implode(' &amp; ', $productOrVariant->getFeedCategoryList($parentProduct, false, 'google_name')),
            'item_group_id' => $parentProduct ? $parentProduct->getKey() : $productOrVariant->getKey(),
        ]);
    }

    public function data()
    {
        return view('admineshop::xml.google', [
            'items' => $this->getItems(),
        ])->render();
    }
}
?>