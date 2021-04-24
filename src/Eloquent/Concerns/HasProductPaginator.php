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

    private $filterParams = [];

    private $extractVariants = false;

    /**
     * Paginate the given query.
     *
     * @param  int|null  $perPage
     * @param  array  $filterParams
     * @param  bool|callable  $extractVariants
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return AdminEshop\Eloquent\Paginator\ProductsPaginator
     *
     * @throws \InvalidArgumentException
     */
    public function scopeProductsPaginate($query, $perPage = null, $filterParams = [], $extractVariants = false, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->filterParams = is_array($filterParams) ? $filterParams : [];

        $this->extractVariants = $extractVariants ?: false;

        $items = $query->get();

        [
            $items,
            $options,
        ] = $this->filterItems($items);

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->getPerPage();

        $paginator = new ProductsPaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
            'pushIntoArray' => $options,
        ]);

        return $paginator;
    }

    private function filterItems($items)
    {
        if ( $this->extractVariants !== false ){
            $this->extractVariants($items);
        }

        $this->filterItemsByPrice($items);
        $this->sortItems($items);

        //Sort collected prices
        $prices = collect($this->pricesTree)->sort()->values();

        return [
            $items,
            [
                'price_range' => [$prices->first() ?: 0, $prices->last() ?: 0],
            ]
        ];
    }

    private function extractVariants(&$items)
    {
        $extractedItems = collect();

        foreach ($items as $product) {
            if ( $product->isType('variants') ){
                $variantsToExtract = $product->variants;

                //We cant make unique variants based for example on color
                if ( is_callable($this->extractVariants) ) {
                    $variantsToExtract = $variantsToExtract->unique($this->extractVariants)->values();
                }

                foreach ($variantsToExtract as $variant) {
                    $clonedProduct = clone $product;
                    $clonedProduct->setRelation('variants', collect([ $variant ]));

                    $extractedItems[] = $clonedProduct;
                }
            } else {
                $extractedItems[] = $product;
            }
        }

        $items = $extractedItems;
    }

    private function getVariantsKey($product)
    {
        return $product->getKey().'-'.$product->getAttribute('variants')->pluck('id')->join('-');
    }

    private function filterItemsByPrice(&$items)
    {
        $items = $items->filter(function($product) {
            $price = in_array($product->product_type, Store::variantsProductTypes())
                        ? $product->getAttribute('cheapestVariantClientPrice')
                        : $product->getAttribute('clientPrice');

            //Collect all prices, to be able calculate price-range
            $this->pricesTree[$this->getVariantsKey($product)] = $price;

            //Filter by price
            if ( is_bool($filterPriceResponse = $this->isPriceInRange($price)) ) {
                return $filterPriceResponse;
            }

            return true;
        })->values();
    }

    private function isPriceInRange($price)
    {
        $priceRanges = array_filter(explode(',', $this->filterParams['_price'] ?? ''), function($item){
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

    private function sortItems(&$items)
    {
        if ( !($sortBy = ($this->filterParams['_sort'] ?? null)) ){
            return;
        }

        $desc = in_array($sortBy, ['expensive']);

        $items = $items->{ $desc ? 'sortByDesc' : 'sortBy' }(function($item) use ($sortBy) {
            if ( in_array($sortBy, ['cheapest', 'expensive']) ) {
                return $this->pricesTree[$this->getVariantsKey($item)];
            }

            return $this->getKey();
        });
    }
}