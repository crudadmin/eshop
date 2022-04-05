<?php

namespace AdminEshop\Contracts\Listing;

use Illuminate\Support\Facades\Cache;
use Store;
use Admin;

trait HasListingSupport
{
    public function defaultLimit()
    {
        return 20;
    }

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
            //Available attributes for products set
            $attributes = Store::getFilterAttributes()->each(function($attribute){
                $attribute->setListingResponse();
            });

            //Paginated response
            $products = $this->getProducts($options);

            return [
                'defaultLimit' => $this->defaultLimit(),
                'pagination' => $products,
                'attributes' => $attributes,
            ];
        });
    }

    public function getProducts($options)
    {
        $productsQuery = Admin::getModel('Product')->withListingResponse($options);

        $products = $productsQuery->productsPaginate(
            request('limit', $this->defaultLimit())
        );

        $products->getCollection()->each->setListingResponse();

        return $products;
    }

    private function getCachedListingResponse(string $cacheKey, array $options, callable $response)
    {
        $cacheMinutes = config('admineshop.routes.listing.cache', 0);

        //Return cached response
        if ( is_numeric($cacheMinutes) && $cacheMinutes >= 1 ){
            $cacheKey = $this->buildCacheKey($cacheKey, $options);

            return json_decode(Cache::remember($cacheKey, $cacheMinutes * 60, function() use ($response, $options) {
                return json_encode($response($options));
            }), true);
        }

        return $response($options);
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