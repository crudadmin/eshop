<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Eloquent\Paginator\ProductsPaginator;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
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
    public function scopeProductsPaginate($query, $perPage = null, $filterParams = [], $columns = ['*'], $pageName = 'page', $page = null)
    {
        $this->filterParams = is_array($filterParams) ? $filterParams : [];

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        //TODO:
        $extractVariants = false;

        $perPage = $perPage ?: $this->getPerPage();

        $totalResult = $this
            ->newQuery()
            ->selectRaw('count(*) as aggregate')
            ->withMinAndMaxFilterPrices($extractVariants)
            ->applyQueryFilter($filterParams, $extractVariants)
            ->get();

        $totalAttributes = $totalResult[0]->attributes;
        $priceRanges = array_filter([$totalAttributes['min_filter_price'] ?? 0, $totalAttributes['max_filter_price'] ?? 0, $totalAttributes['min_filter_variant_price'] ?? 0, $totalAttributes['max_filter_variant_price'] ?? 0], function($number){
            return is_numeric($number) && $number > 0;
        });

        $total = $totalAttributes['aggregate'];

        $results = $total
                    ? $query->forPage($page, $perPage)->get($columns)
                    : $this->newCollection();

        $paginator = new ProductsPaginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
            'pushIntoArray' => [
                'price_range' => count($priceRanges) >= 1 ? [min($priceRanges), max($priceRanges)] : [],
            ],
        ]);

        return $paginator;
    }

    public function scopeWithMinAndMaxFilterPrices($query, $extractVariants)
    {
        $query->addSelect(DB::raw('MIN(products.price) as min_filter_price, MAX(products.price) as max_filter_price'));

        if ( $extractVariants === false ) {
            $query->leftJoin('products as pv', 'pv.product_id', '=', 'products.id');

            $query->addSelect(DB::raw('MIN(pv.price) as min_filter_variant_price, MAX(pv.price) as max_filter_variant_price'));
        }
    }

    // private function filterItems($items)
    // {
    //     if ( $this->extractVariants !== false ){
    //         // $this->extractVariants($items);
    //     }

    //     $this->filterItemsByPrice($items);
    //     $this->sortItems($items);

    //     //Sort collected prices
    //     $prices = collect($this->pricesTree)->sort()->values();

    //     return [
    //         $items,
    //         [
    //             'price_range' => [$prices->first() ?: 0, $prices->last() ?: 0],
    //         ]
    //     ];
    // }

    // private function extractVariants(&$items)
    // {
    //     $extractedItems = collect();

    //     foreach ($items as $product) {
    //         if ( $product->isType('variants') ){
    //             $variantsToExtract = $product->variants;

    //             //We cant make unique variants based for example on color
    //             if ( is_callable($this->extractVariants) ) {
    //                 $variantsToExtract = $variantsToExtract->unique($this->extractVariants)->values();
    //             }

    //             foreach ($variantsToExtract as $variant) {
    //                 $clonedProduct = clone $product;
    //                 $clonedProduct->setRelation('variants', collect([ $variant ]));

    //                 $extractedItems[] = $clonedProduct;
    //             }
    //         } else {
    //             $extractedItems[] = $product;
    //         }
    //     }

    //     $items = $extractedItems;
    // }

    private function getVariantsKey($product)
    {
        $variantsKey = $product->relationLoaded('variants') ? $product->getAttribute('variants')->pluck('id')->join('-') : 0;

        return $product->getKey().'-'.$variantsKey;
    }

    // private function filterItemsByPrice(&$items)
    // {
    //     $items = $items->filter(function($product) {
    //         $price = in_array($product->product_type, Store::variantsProductTypes())
    //                     ? $product->getAttribute('cheapestVariantClientPrice')
    //                     : $product->getAttribute('clientPrice');

    //         //Collect all prices, to be able calculate price-range
    //         $this->pricesTree[$this->getVariantsKey($product)] = $price;

    //         //Filter by price
    //         if ( is_bool($filterPriceResponse = $this->isPriceInRange($price)) ) {
    //             return $filterPriceResponse;
    //         }

    //         return true;
    //     })->values();
    // }

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
        $filter = $this->filterParams;

        $existingAttributes = Store::getExistingAttributesFromFilter($filter);

        if ( !($sortBy = ($filter['_sort'] ?? null)) && count($existingAttributes) == 0 ){
            return;
        }

        //Sort by sorter
        if ( $sortBy ) {
            $desc = in_array($sortBy, ['expensive']);

            $items = $items->{ $desc ? 'sortByDesc' : 'sortBy' }(function($item) use ($sortBy) {
                if ( in_array($sortBy, ['cheapest', 'expensive']) ) {
                    return $this->pricesTree[$this->getVariantsKey($item)];
                }

                return $this->getKey();
            });
        }

        //Sort by filter score
        else {
            $items = $items->sortByDesc(function($item) use ($existingAttributes) {
                return $this->getProductFilterScore($item, $existingAttributes);
            });
        }
    }

    private function getProductFilterScore($item, $existingAttributes)
    {
        $filter = $this->filterParams;

        $maxAttributeScore = count($existingAttributes);

        $score = 0;

        if ( $item->attributesItems->count() ) {
            $attributesItems = $item->attributesItems->groupBy('attribute_id');

            //On each attribute match we will add +10 score
            $aScore = 1;
            foreach ($existingAttributes as $filterAttribute) {
                if ( !($attributesItems[$filterAttribute['id']] ?? null) ){
                    continue;
                }

                $requestedFilterValue = explode(',', $filter[$filterAttribute['slug']]);

                //Add higher score when attribute is set as first, and low score when attribute match is for example on the third place
                foreach ($attributesItems[$filterAttribute['id']] as $i => $attrItem) {
                    if ( in_array($attrItem->attributes_item_id, $requestedFilterValue) ) {
                        //On each attribute match add aScore
                        $aScore++;

                        //On each attributeMatch we add 10 score, but we need substract position of item match.
                        //If item is in first order, we will add full 10 score
                        //if item is in second order, we will add 10-1=>9 score, because it is not primary value for this match.
                        //So if two products will be set as red color, but second product is primary brown and secondary red
                        //then both products will be in front, but product with brown will have lower score.
                        $score += ($aScore * 10) - $i;
                    }
                }
            }
        }

        if ( ($variants = $item->getAttribute('variants')) && $variants->count() ){
            foreach ($variants as $variant) {
                $score += $this->getProductFilterScore($variant, $existingAttributes);
            }
        }

        return $score;
    }
}