<?php

namespace AdminEshop\Contracts\Listing;

use Illuminate\Support\Facades\Cache;

trait HasListingSupport
{
    protected function getCachableQueries()
    {
        return ['page', 'limit'];
    }

    public function getCachedListingResponse(string $cacheKey, array $filter, callable $response)
    {
        $cacheMinutes = config('admineshop.routes.listing.cache', 0);

        //Return cached response
        if ( is_numeric($cacheMinutes) && $cacheMinutes >= 1 ){
            $cacheKey = $this->buildCacheKey($cacheKey, $filter);

            return json_decode(Cache::remember($cacheKey, $cacheMinutes * 60, function() use ($response) {
                return json_encode($response());
            }));
        }

        return $response();
    }

    private function buildCacheKey($cacheKey, $filter)
    {
        $items = [
            $cacheKey,
        ];

        //Get filter hash
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