<?php

namespace AdminEshop\Contracts\Listing;

use Illuminate\Support\Facades\Cache;
use Store;
use Admin;

trait HasListingSupport
{
    public function getCachableQueries()
    {
        return ['page', 'limit'];
    }

    public function getFilter()
    {
        return request('filter', []);
    }

    public function getListingResponse($options = [])
    {
        //Set default filter, if is not set
        $options['filter'] = $options['filter'] ?? $this->getFilter();

        return $this->getCachedListingResponse($options['key'] ?? 'listing', $options, function($options) {
            //We want filter out items without products
            if ( config('admineshop.attributes.hideOnFiltration', true) ){
                Store::setAttributesScope($options);
            }

            //Available attributes for products set
            $attributes = Store::getFilterAttributes()->each->setListingResponse()->values();

            //Paginated response
            $products = $this->getProducts($options);

            return [
                'pagination' => $products,
                'attributes' => $attributes,
            ];
        });
    }

    public function getProducts($options)
    {
        $productsQuery = Admin::getModel('Product')->withListingResponse($options);

        $products = $productsQuery->productsPaginate(
            request('filter._limit', request('limit'))
        );

        $products->getCollection()->each->setListingResponse();

        return $products;
    }

    private function getCachedListingResponse(string $cacheKey, array $options, callable $response)
    {
        //Return cached response
        if ( $this->canUseCache($options) ){
            $cacheKey = $this->buildCacheKey($cacheKey, $options);

            return json_decode(Cache::remember($cacheKey, $this->getCacheMinutage() * 60, function() use ($response, $options) {
                return json_encode($response($options));
            }), true);
        }

        return $response($options);
    }

    public function canUseCache($options)
    {
        //If search key is presnet, we need disable cache
        if ( $options['filter']['_search'] ?? null ){
            return false;
        }

        //If cache minutage is present
        return (is_numeric($min = $this->getCacheMinutage())) && $min >= 1;
    }

    private function getCacheMinutage()
    {
        return config('admineshop.routes.listing.cache', 0);
    }

    private function buildCacheKey($cacheKey, $options)
    {
        $items = [
            $cacheKey,
        ];

        //Get filter hash
        $filter = $options['filter'] ?? null;
        if ( is_array($filter) && count($filter) > 0 ){
            $items[] = crc32(json_encode($filter));
        }

        //If page is present
        foreach ($this->getCachableQueries() as $i => $queryName) {
            if ( is_null($value = request($queryName)) === false ){
                $items[] = $queryName.'.'.$value;
            }
        }

        return implode('.', $items);
    }
}
?>