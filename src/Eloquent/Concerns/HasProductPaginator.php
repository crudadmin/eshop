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
    private $pricesTree;

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

        $this->filterItemsByPrice($items, $filterParams);
        $this->sortItems($items, $filterParams);

        //Sort collected prices
        $prices = collect($this->pricesTree)->sort()->values();

        return [
            $items,
            [
                'price_range' => [$prices->first() ?: 0, $prices->last() ?: 0],
            ]
        ];
    }

    private function filterItemsByPrice(&$items, $filterParams)
    {
        $items = $items->filter(function($product) use ($filterParams) {
            $price = in_array($product->product_type, Store::variantsProductTypes())
                        ? $product->getAttribute('cheapestVariantClientPrice')
                        : $product->getAttribute('clientPrice');

            //Collect all prices, to be able calculate price-range
            $this->pricesTree[$product->getKey()] = $price;

            //Filter by price
            if ( is_bool($filterPriceResponse = $this->isPriceInRange($price, $filterParams)) ) {
                return $filterPriceResponse;
            }

            return true;
        })->values();
    }

    private function isPriceInRange($price, array $filterParams)
    {
        $priceRanges = array_filter(explode(',', $filterParams['_price'] ?? ''), function($item){
            return !is_null($item) && $item !== '';
        });

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

    private function sortItems(&$items, array $filterParams)
    {
        if ( !($sortBy = ($filterParams['_sort'] ?? null)) ){
            return;
        }

        $desc = in_array($sortBy, ['expensive']);

        $items = $items->{ $desc ? 'sortByDesc' : 'sortBy' }(function($item) use ($sortBy) {
            if ( in_array($sortBy, ['cheapest', 'expensive']) ) {
                return $this->pricesTree[$item->getKey()];
            }

            return $this->getKey();
        });
    }
}