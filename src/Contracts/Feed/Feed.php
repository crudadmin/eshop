<?php

namespace AdminEshop\Contracts\Feed;

use Admin;
use Cache;
use Store;

class Feed
{
    public function getContentType()
    {
        return $this->contentType;
    }

    public function enabled()
    {
        return true;
    }

    public function cacheDuration()
    {
        return config('admineshop.feeds.cache', 3600);
    }

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

    public function getCachedData()
    {
        return Cache::remember('store.feed.'.static::class, $this->cacheDuration(), function(){
            return $this->data();
        });
    }
}