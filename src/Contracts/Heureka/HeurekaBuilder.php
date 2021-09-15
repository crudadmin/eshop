<?php

namespace AdminEshop\Contracts\Heureka;

use Admin;
use Store;

class HeurekaBuilder
{
    public function getItems()
    {
        $items = collect();

        $products = Admin::getModel('Product')->getHeurekaListing();
        $hasVariants = count(Store::variantsProductTypes()) > 0;

        foreach ($products as $product)
        {
            if ( $hasVariants && $product->isType('variants') ){
                foreach ($product->variants as $variant) {
                    $items->push($variant->toHeurekaArray($product));
                }
            } else {
                $items->push($product->toHeurekaArray());
            }
        }

        return $items;
    }
}
?>