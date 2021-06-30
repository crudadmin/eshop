<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;
use Store;
use DB;

trait HasProductFilter
{
    public function scopeFilterCategory($query, $category)
    {
        $query->whereHas('categories', function($query) use ($category) {
            $query
                ->where('categories.id', $category->getKey())
                ->withoutGlobalScope('order');
        });
    }

    public function scopeFilterAttributeItems($query, int $attributeId, array $itemIds)
    {
        $query->whereHas('attributesItems', function($query) use ($attributeId, $itemIds) {
            $query->whereIn('attributes_item_product_attributes_list.attributes_item_id', $itemIds);
        });
    }

    private function getFilterFromQuery($params)
    {
        $params = is_array($params) ? $params : [];

        $filter = [];

        //If no filter params are present
        if ( count($params) > 0 ){
            $existingAttributes = Store::getExistingAttributesFromFilter($params);

            foreach ($params as $key => $value) {
                //Denny non attributes queries
                if ( array_key_exists($key, $existingAttributes) ){
                    $attributeId = $existingAttributes[$key]['id'];

                    $filter[$attributeId] = explode(',', $value);
                }
            }
        }

        return $filter;
    }

    public function scopeApplyQueryFilter($query, $params, $extractVariants = false)
    {
        //Filter whole products
        $query->where(function($query) use ($params, $extractVariants) {
            //Filter by basic product type
            $query->where(function($query) use ($params, $extractVariants) {
                $query->where(function($query) use ($extractVariants) {
                    $query->nonVariantProducts();

                    if ( is_array($extractVariants) ){
                        $query->orWhere(function($query){
                            $query->variantProducts();
                        });
                    }
                });

                $query->filterProduct($params);
            });

            if ( $extractVariants === false ) {
                //Filter product by variant attributes
                $query->orWhere(function($query) use ($params) {
                    $query
                        ->variantsProducts()
                        ->whereHas('variants', function($query) use ($params) {
                            $query->filterProduct($params);
                        });
                });
            }
        });

        $this->extractDifferentVariants($extractVariants);
    }

    public function scopeExtractDifferentVariants($query, $extractVariants)
    {
        if ( $extractVariants === false ) {
            return;
        }

        $attributesList = DB::table('attributes_item_product_attributes_list')
                          ->selectRaw('
                            product_id,
                            CONCAT(product_id, "_", attributes_items.attribute_id) as variant_groupper,
                            CONCAT(attributes_items.attribute_id, "_", GROUP_CONCAT(attributes_item_id)) as attributes_groupper
                        ')
                        ->leftJoin('attributes_items', 'attributes_items.id', '=', 'attributes_item_product_attributes_list.attributes_item_id')
                        ->whereIn('attributes_items.attribute_id', $extractVariants)
                        ->groupBy('variant_groupper');

        $query->leftJoinSub($attributesList, 'attributesList', function($join){
            $join->on('attributesList.product_id', '=', 'products.id');
        });

        $query->addSelect(DB::raw('CONCAT(products.product_id, "-", attributesList.attributes_groupper) as groupper'))
              ->groupBy('groupper');
    }

    public function scopeApplyPriceRangeFilter($query, $params, $extractVariants = false)
    {
        $priceRanges = array_filter(explode(',', $params['_price'] ?? ''), function($item){
            return !is_null($item) && $item !== '';
        });

        $filterCount = count($priceRanges);

        //Filter product range price
        if ( $filterCount == 2 ){
            $query->where(function($query) use ($priceRanges) {
                $query
                    ->where($query->getQuery()->from.'.price', '>=', $priceRanges[0])
                    ->where($query->getQuery()->from.'.price', '<=', $priceRanges[1]);
            });
        } else if ( $filterCount == 1 ) {
            $query->where($query->getQuery()->from.'.price', '<=', $priceRanges[0]);
        }
    }

    public function scopeFilterProduct($query, $params)
    {
        $filter = $this->getFilterFromQuery($params);

        $query->withoutGlobalScope('order');

        foreach ($filter as $attributeId => $itemIds) {
            $query->filterAttributeItems($attributeId, $itemIds);
        }

        $query->applyPriceRangeFilter($params);
    }
}