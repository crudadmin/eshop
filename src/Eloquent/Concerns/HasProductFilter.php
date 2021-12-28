<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Models\Products\Pivot\ProductsCategoriesPivot;
use AdminEshop\Models\Products\Product;
use Admin\Eloquent\AdminModel;
use DB;
use Store;

trait HasProductFilter
{
    public function categoriesPivot()
    {
        return $this->hasMany(ProductsCategoriesPivot::class);
    }

    public function scopeFilterCategory($query, $category)
    {
        $query->whereHas('categoriesPivot', function($query) use ($category) {
            if ( is_numeric($category) ) {
                $query->where('category_product_categories.category_id', $category->getKey());
            } else if ( is_array($category) ){
                if ( count($category) == 1 ) {
                    $query->where('category_product_categories.category_id', $category[0]);
                } else {
                    $query->whereIn('category_product_categories.category_id', $category);
                }
            } else if ( $category instanceof AdminModel ) {
                $query->where('category_product_categories.category_id', $category->getKey());
            }
        });
    }

    public function scopeFilterAttributeItems($query, int $attributeId, array $itemIds)
    {
        $query->whereHas('attributesItems', function($query) use ($attributeId, $itemIds) {
            $query->whereIn('attributes_item_product_attributes_items.attributes_item_id', $itemIds);
        });
    }

    private function getFilterFromQuery($params)
    {
        $params = is_array($params) ? $params : [];

        $filter = $this->getFilterFromParams($params);

        return $filter;
    }

    public function scopeApplyQueryFilter($query)
    {
        $filter = $this->getFilterOption('filter', []);
        $extractVariants = $this->getFilterOption('listing.variants.extract', false);

        //Apply user filter scope
        if ( $scope = $this->getFilterOption('scope') ){
            $scope($query);
        }

        $query->applyCategoryFilter($filter);

        //Filter whole products
        $query->where(function($query) use ($filter, $extractVariants) {
            //Filter by basic product type
            $query->where(function($query) use ($filter, $extractVariants) {
                $query->where(function($query) use ($extractVariants) {
                    $query->nonVariantProducts();

                    if ( is_array($extractVariants) ){
                        $query->orWhere(function($query){
                            $query->variantProducts();
                        });
                    }
                });

                $query->filterProduct($filter);
            });

            if ( $extractVariants === false ) {
                //Filter product by variant attributes
                $query->orWhere(function($query) use ($filter) {
                    $query
                        ->variantsProducts()
                        ->whereHas('variants', function($query) use ($filter) {
                            //Apply user filter scope on variants
                            if ( $scope = $this->getFilterOption('scope.variants') ){
                                $scope($query);
                            }

                            $query->filterProduct($filter);
                        });
                });
            }
        });

        $this->extractDifferentVariants($extractVariants);
    }

    public function scopeApplyCategoryFilter($query, $params)
    {
        //Filter by category
        if ( !($categoryFilter = $params['_categories'] ?? null) ) {
            return;
        }

        $categoryFilter = is_array($categoryFilter)
                            ? $categoryFilter
                            : explode(',', $categoryFilter.'');

        $query->filterCategory($categoryFilter);
    }

    public function scopeExtractDifferentVariants($query, $extractVariants)
    {
        if ( $extractVariants === false ) {
            return;
        }

        $attributesList = DB::table('attributes_item_product_attributes_items')
                          ->selectRaw('
                            product_id,
                            CONCAT(product_id, "_", attributes_items.attribute_id) as variant_groupper,
                            CONCAT(attributes_items.attribute_id, "_", GROUP_CONCAT(attributes_item_id)) as attributes_groupper
                        ')
                        ->leftJoin('attributes_items', 'attributes_items.id', '=', 'attributes_item_product_attributes_items.attributes_item_id')
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

        //Filter product range price
        $query->where(function($query) use ($priceRanges) {
            $filterCount = count($priceRanges);

            if ( $filterCount == 2 ){
                $query
                    ->where($query->getQuery()->from.'.price', '>=', $priceRanges[0])
                    ->where($query->getQuery()->from.'.price', '<=', $priceRanges[1]);
            } else if ( $filterCount == 1 ) {
                $query->where($query->getQuery()->from.'.price', '<=', $priceRanges[0]);
            }
        });
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

    public function getFilterFromParams($params)
    {
        $filter = [];

        //If no filter params are present
        if ( $params && count($params) > 0 ){
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
}