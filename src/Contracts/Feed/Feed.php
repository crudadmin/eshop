<?php

namespace AdminEshop\Contracts\Feed;

use Admin;
use Cache;
use Store;

class Feed
{
    private $locale;

    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    //Check if given feed is registered
    public static function isEnabled()
    {
        return in_array(static::class, config('admineshop.feeds.providers'));
    }

    public function cacheDuration()
    {
        return config('admineshop.feeds.cache', 3600);
    }

    public function getProducts($query)
    {
        return $query;
    }

    public function getItems()
    {
        $callback = function(){
            $items = collect();

            $products = $this->getProducts(
                Admin::getModel('Product')->withFeedResponse()
            )->get();

            $hasVariants = count(Store::variantsProductTypes()) > 0;

            foreach ($products as $i => $product)
            {
                //Testing
                // if ( $i > 10 ){
                //     continue;
                // }

                if ( $hasVariants && $product->isType('variants') ){
                    foreach ($product->variants as $variant) {
                        $items->push(
                            $this->getFeedArray($variant, $product)
                        );
                    }
                } else {
                    $items->push(
                        $this->getFeedArray($product)
                    );
                }
            }

            return $items;
        };

        if ( config('admineshop.feeds.debug', false) ){
            return $callback();
        }

        return Cache::remember($this->cacheKey('items'), 60 * 5, $callback);
    }

    public function getFeedArray($productOrVariant, $parentProduct = null)
    {
        $array = $this->toFeedArray($productOrVariant, $parentProduct);

        $mutatorName = 'to'.class_basename(static::class).'Array';

        if ( method_exists($productOrVariant, $mutatorName) ){
            $array = array_merge($array, $productOrVariant->{$mutatorName}($parentProduct));
        }

        return $array;
    }

    public function toFeedArray($productOrVariant, $parentProduct = null)
    {
        $array = $productOrVariant->setVisible([
            'id', 'name', 'code', 'ean', 'description',
            'priceWithVat',
        ])->append([
            'priceWithVat',
        ])->setLocalizedResponse()->toArray();

        $array = [
            'id' => $productOrVariant->feedItemId,
            'name' => $productOrVariant->name,
            'vat' => Store::getVatValueById($productOrVariant->vat_id),
            'feed_url' => $productOrVariant->getFeedUrl($parentProduct),
            'feed_thumbnail' => $productOrVariant->getFeedThumbnail($parentProduct),
            'quantity' => $productOrVariant->stock_quantity,
        ] + $array;

        foreach ($array as $key => $value) {
            $array[$key] = is_string($value) ? trim($value) : $value;
        }

        return $array;
    }

    public function cacheKey($key)
    {
        return 'store.feed.'.static::class.'.'.$key.'.'.($this->locale ?: 'default');
    }

    public function getCachedData()
    {
        return Cache::remember($this->cacheKey('data'), $this->cacheDuration(), function(){
            return $this->data();
        });
    }
}