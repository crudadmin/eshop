<?php

namespace AdminEshop\Contracts\Heureka;

use Admin;

class HeurekaBuilder
{
    public function getItems()
    {
        $items = collect();

        $products = Admin::getModel('Product')->getHeurekaListing();

        foreach ($products as $product)
        {
            if ( $product->isType('variants') ){
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