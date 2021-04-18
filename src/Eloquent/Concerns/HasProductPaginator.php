<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Eloquent\Paginator\ProductsPaginator;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;
use Illuminate\Pagination\Paginator;
use Store;

trait HasProductPaginator
{
    /**
     * Paginate the given query.
     *
     * @param  int|null  $perPage
     * @param  array  $filterParams
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return AdminEshop\Eloquent\Paginator\ProductsPaginator
     *
     * @throws \InvalidArgumentException
     */
    public function scopeProductsPaginate($query, $perPage = null, $filterParams = [], $columns = ['*'], $pageName = 'page', $page = null)
    {
        $items = $query->get();

        [
            $items,
            $options,
        ] = $this->filterItems($items, $filterParams);

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->getPerPage();

        $paginator = new ProductsPaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
            'pushIntoArray' => $options,
        ]);

        return $paginator;
    }

    private function filterItems($items, $filterParams = [])
    {
        $filterParams = is_array($filterParams) ? $filterParams : [];

        $prices = [];

        $items = $items->filter(function($product) use (&$prices, $filterParams) {
            $price = in_array($product->product_type, Store::variantsProductTypes())
                        ? $product->getAttribute('cheapestVariantClientPrice')
                        : $product->getAttribute('clientPrice');

            //Collect all prices, to be able calculate price-range
            $prices[] = $price;

            //Filter by price
            if ( is_bool($filterPriceResponse = $this->filterByPrice($price, $filterParams)) ) {
                return $filterPriceResponse;
            }

            return true;
        })->values();

        //Sort collected prices
        $prices = collect($prices)->sort()->values();

        return [
            $items,
            [
                'price_range' => [$prices->first() ?: 0, $prices->last() ?: 0],
            ]
        ];
    }

    private function filterByPrice($price, array $filterParams)
    {
        $priceRanges = array_filter(explode(',', $filterParams['_price'] ?? ''));

        $filterCount = count($priceRanges);

        //Filter product range price
        if ( $filterCount == 2 ){
            return $price >= $priceRanges[0] && $price <= $priceRanges[1];
        }

        //Filter product by max price
        else if ( $filterCount == 1 ){
            return $price <= $priceRanges[0];
        }
    }
}