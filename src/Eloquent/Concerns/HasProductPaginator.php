<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Eloquent\Paginator\ProductsPaginator;
use AdminEshop\Models\Products\Product;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Store;

trait HasProductPaginator
{
    private $pricesTree;

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
    public function scopeProductsPaginate($query, $perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->getPerPage();

        $totalResult = $this
            ->newQuery()
            ->selectRaw('count(*) as aggregate_products')
            ->withMinAndMaxFilterPrices()
            ->applyQueryFilter()
            ->get();

        $totalAttributes = $totalResult[0]->attributes;

        $priceRanges = $this->getProductPriceRanges($totalAttributes);

        $total = $totalAttributes['aggregate_products'];

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

    /**
     * TODO: does not support prices with VAT
     *
     * @param  array  $attrs
     */
    private function getProductPriceRanges($attrs)
    {
        $keys = [
            'min_filter_price',
            'max_filter_price',
            'min_filter_variant_price',
            'max_filter_variant_price',
            'min_filter_level_price',
            'max_filter_level_price',
            'min_filter_variant_level_price',
            'max_filter_variant_level_price',
        ];

        $ranges = [];

        foreach ($keys as $key) {
            $isLevelPrice = strpos($key, '_level_price') !== false;

            if ( isset($attrs[$key]) && is_numeric($attrs[$key]) ){
                $value = $attrs[$key];

                //If price level is available, we want reset original product price for comparison.
                if ( $isLevelPrice == false ){
                    $value = Store::calculateFromDefaultCurrency($value);
                }

                $ranges[$key] = (float)$value;
            }
        }

        return array_filter($ranges, function($number){
            return is_numeric($number);
        });
    }

    public function scopeWithMinAndMaxFilterPrices($query)
    {
        $hasPriceLevels = config('admineshop.prices.price_levels');

        //Load pricelevels for base products
        if ( $hasPriceLevels ){
            $query->withPriceLevels();

            //Add maxs of default prices (only count max, when price level is available)
            $query->addSelect(DB::raw('
                MIN(IF(pl.price is NULL, products.price, NULL)) as min_filter_price,
                MAX(IF(pl.price is NULL, products.price, NULL)) as max_filter_price,
                MIN(pl.price) as min_filter_level_price,
                MAX(pl.price) as max_filter_level_price
            '));
        } else {
            $query->addSelect(DB::raw('
                MIN(products.price) as min_filter_price,
                MAX(products.price) as max_filter_price
            '));
        }

        if ( $this->getFilterOption('variants.extract') === false ) {
            $variants = DB::table('products')
                            ->selectRaw('products.product_id')
                            ->whereNotNull('products.product_id')
                            ->whereNull('products.deleted_at')
                            ->groupBy('products.product_id');

            //Select maxs of default prices or price levels
            if ( $hasPriceLevels ){
                $variants->addSelect(DB::raw('
                    MIN(IF(pl.price is NULL, products.price, NULL)) as min_filter_variant_price,
                    MAX(IF(pl.price is NULL, products.price, NULL)) as max_filter_variant_price,
                    MIN(pl.price) as min_filter_variant_level_price,
                    MAX(pl.price) as max_filter_variant_level_price
                '));
            }

            //Select only default prices
            else {
                $variants->addSelect(DB::raw('
                    MIN(products.price) as min_filter_variant_price,
                    MAX(products.price) as max_filter_variant_price
                '));
            }

            $this->scopeWithPriceLevels($variants);

            $query->leftJoinSub($variants, 'pvp', function($join){
                $join->on('products.id', '=', 'pvp.product_id');
            });

            $query->addSelect(DB::raw('
                MIN(pvp.min_filter_variant_price) as min_filter_variant_price,
                MAX(pvp.max_filter_variant_price) as max_filter_variant_price
            '));

            if ( $hasPriceLevels ){
                $query->addSelect(DB::raw('
                    MAX(pvp.max_filter_variant_level_price) as max_filter_variant_level_price,
                    MIN(pvp.min_filter_variant_level_price) as min_filter_variant_level_price
                '));
            }
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

    // private function isPriceInRange($price)
    // {
    //     $priceRanges = array_filter(explode(',', $this->filterParams['_price'] ?? ''), function($item){
    //         return !is_null($item) && $item !== '';
    //     });

    //     $filterCount = count($priceRanges);

    //     //Filter product range price
    //     if ( $filterCount == 2 ){
    //         return $price >= $priceRanges[0] && $price <= $priceRanges[1];
    //     }

    //     //Filter product by max price
    //     else if ( $filterCount == 1 ){
    //         return $price <= $priceRanges[0];
    //     }
    // }

    // private function sortItems(&$items)
    // {
    //     $filter = $this->filterParams;

    //     $existingAttributes = Store::getExistingAttributesFromFilter($filter);

    //     if ( !($sortBy = ($filter['_sort'] ?? null)) && count($existingAttributes) == 0 ){
    //         return;
    //     }

    //     //Sort by sorter
    //     if ( $sortBy ) {
    //         $desc = in_array($sortBy, ['expensive']);

    //         $items = $items->{ $desc ? 'sortByDesc' : 'sortBy' }(function($item) use ($sortBy) {
    //             if ( in_array($sortBy, ['cheapest', 'expensive']) ) {
    //                 return $this->pricesTree[$this->getVariantsKey($item)];
    //             }

    //             return $this->getKey();
    //         });
    //     }

    //     //Sort by filter score
    //     else {
    //         $items = $items->sortByDesc(function($item) use ($existingAttributes) {
    //             return $this->getProductFilterScore($item, $existingAttributes);
    //         });
    //     }
    // }

    // private function getProductFilterScore($item, $existingAttributes)
    // {
    //     $filter = $this->filterParams;

    //     $maxAttributeScore = count($existingAttributes);

    //     $score = 0;

    //     if ( $item->attributesItems->count() ) {
    //         $attributesItems = $item->attributesItems->groupBy('attribute_id');

    //         //On each attribute match we will add +10 score
    //         $aScore = 1;
    //         foreach ($existingAttributes as $filterAttribute) {
    //             if ( !($attributesItems[$filterAttribute['id']] ?? null) ){
    //                 continue;
    //             }

    //             $requestedFilterValue = explode(',', $filter[$filterAttribute['slug']]);

    //             //Add higher score when attribute is set as first, and low score when attribute match is for example on the third place
    //             foreach ($attributesItems[$filterAttribute['id']] as $i => $attrItem) {
    //                 if ( in_array($attrItem->attributes_item_id, $requestedFilterValue) ) {
    //                     //On each attribute match add aScore
    //                     $aScore++;

    //                     //On each attributeMatch we add 10 score, but we need substract position of item match.
    //                     //If item is in first order, we will add full 10 score
    //                     //if item is in second order, we will add 10-1=>9 score, because it is not primary value for this match.
    //                     //So if two products will be set as red color, but second product is primary brown and secondary red
    //                     //then both products will be in front, but product with brown will have lower score.
    //                     $score += ($aScore * 10) - $i;
    //                 }
    //             }
    //         }
    //     }

    //     if ( ($variants = $item->getAttribute('variants')) && $variants->count() ){
    //         foreach ($variants as $variant) {
    //             $score += $this->getProductFilterScore($variant, $existingAttributes);
    //         }
    //     }

    //     return $score;
    // }
}