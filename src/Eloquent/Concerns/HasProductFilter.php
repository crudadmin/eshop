<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Models\Products\Pivot\ProductsCategoriesPivot;
use Admin\Eloquent\AdminModel;
use DB;
use Store;
use Exception;

trait HasProductFilter
{
    public function categoriesPivot()
    {
        return $this->hasMany(ProductsCategoriesPivot::class);
    }

    public function scopeFilterCategory($query, $category)
    {
        $query->whereHas('categoriesPivot', function($query) use ($category) {
            $category = is_numeric($category) ? array_wrap($category) : $category;

            if ( is_array($category) ){
                $isSingle = count($category) == 1;

                $query->{$isSingle ? 'where' : 'whereIn'}(
                    'category_product_categories.category_id', $isSingle ? $category[0] : $category
                );
            } else if ( $category instanceof AdminModel ) {
                $query->where('category_product_categories.category_id', $category->getKey());
            }
        });
    }

    public function scopeFilterAttributeItems($query, array $itemIds, $attributesItemsScope = null)
    {
        $query->whereHas('attributesItems', function($query) use ($itemIds, $attributesItemsScope) {
            $query->whereIn('attributes_item_product_attributes_items.attributes_item_id', $itemIds);

            if ( is_callable($attributesItemsScope) ){
                $attributesItemsScope($query);
            }
        });
    }

    public function getFilterFromQuery($params)
    {
        $params = is_array($params) ? $params : [];

        $filter = $this->getFilterFromParams($params);

        return $filter;
    }

    public function scopeApplyQueryFilter($query)
    {
        //Filter whole products
        $query->where(function($query) {
            $extractVariants = $this->getFilterOption('variants.extract', false);
            $filter = $this->getFilterOption('filter', []);

            //Filter by basic product type
            $query->where(function($query) use ($extractVariants, $filter) {
                $query->where(function($query) use ($extractVariants) {
                    //Regular product
                    $query->where(function($query) {
                        $query
                            ->nonVariantProducts()
                            ->filterParentProduct();
                    });

                    //Retrieve variants as well
                    if ( $extractVariants ){
                        $query->orWhere(function($query){
                            $query
                                ->variantProducts()
                                ->filterVariantProduct()
                                ->whereHas('product', function($query){
                                    $query
                                        //We need clone settings from parent model
                                        ->cloneModelFilter($this)
                                        ->filterParentProduct();
                                });
                        });
                    }
                });

                $query->filterProduct($filter);
            });

            //Retrieve basic parents of variants types.
            if ( $extractVariants === false ) {
                $query->orWhere(function($query) use ($filter) {
                    $query
                        ->variantsProducts()
                        ->cloneModelFilter($this)
                        ->filterParentProduct()
                        //Only when filter of parent product matches with variants
                        ->whereHas('variants', function($query) use ($filter) {
                            $query
                                //We need reclone options in relationships
                                ->cloneModelFilter($this)
                                ->filterProduct($filter)
                                ->filterVariantProduct();
                        });
                });
            }
        });

        $this->extractDifferentVariants();
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

    public function scopeApplySearchFilter($query, $params)
    {
        //Filter by category
        if ( !($searchQuery = $params['_search'] ?? null) ) {
            return;
        }

        $query->onSearch($searchQuery);
    }

    /**
     * We can modiry search engine in this method
     *
     * @param  Builder  $query
     * @param  string  $searchQuery
     */
    public function scopeOnSearch($query, $searchQuery)
    {
        $query->fulltextSearch($searchQuery);
    }

    public function scopeExtractDifferentVariants($query)
    {
        if ( !($extractor = $this->getFilterOption('variants.extract', false)) ) {
            return;
        }

        //Use default extractor when true value has been returned
        if ( $extractor === true && $extractor = $this->getExtractorAttributes() ){
            $extractor = array_wrap($extractor);
        }

        if ( is_array($extractor) == false || count($extractor) == 0 ){
            // throw new Exception('Extractor attribute ids are not defined.');
            return;
        }

        $attributesList = DB::table('attributes_item_product_attributes_items')
                          ->selectRaw('
                            product_id,
                            CONCAT(product_id, "_", attributes_items.attribute_id) as variant_groupper,
                            CONCAT(attributes_items.attribute_id, "_", GROUP_CONCAT(attributes_item_id)) as attributes_groupper
                        ')
                        ->leftJoin('attributes_items', 'attributes_items.id', '=', 'attributes_item_product_attributes_items.attributes_item_id')
                        ->whereIn('attributes_items.attribute_id', $extractor)
                        ->groupBy('variant_groupper');

        $query->leftJoinSub($attributesList, 'attributesList', function($join){
            $join->on('attributesList.product_id', '=', 'products.id');
        });

        $query->addSelect(DB::raw('CONCAT(products.product_id, "-", attributesList.attributes_groupper) as groupper'))
              ->groupBy('groupper');
    }

    public function scopeApplyPriceRangeFilter($query, $params, $extractVariants = false)
    {
        $priceRanges = [];

        //Filter by single price range
        if ( $price = ($params['_price'] ?? '') ) {
            $priceRanges[] = array_filter(explode(',', $price), function($item){
                return !is_null($item) && $item !== '';
            });
        }

        //Filter by multiple price ranges, eg: 0,9.99 ; 10,99.99
        if ( $prices = ($params['_prices'] ?? '') ) {
            $priceRanges = array_merge($priceRanges, array_map(
                fn($range) => array_map(fn($r) => (float)$r, explode(',', $range)),
                explode(';', $prices)
            ));
        }

        if ( count($priceRanges) > 0 ){
            //Filter product range price
            $query->where(function($query) use ($priceRanges) {
                foreach ($priceRanges as $priceRange) {
                    $query->orWhere(function($query) use ($priceRange){
                        $column = $query->getQuery()->from.'.price';
                        $filterCount = count($priceRange);

                        if ( $filterCount == 2 ){
                            //basic filter from to, eg: 5-200
                            if ( $priceRange[0] < $priceRange[1] ) {
                                $query
                                    ->where($column, '>=', $priceRange[0])
                                    ->where($column, '<=', $priceRange[1]);
                            }

                            //Filter up to 200+: we need pass 200;0, it means we want filter 200 and higher.
                            else if ( $priceRange[0] > $priceRange[1] ){
                                $query->where($column, '>=', $priceRange[0]);
                            }
                        }

                        //Filter x and lower, pass 1 param.
                        else if ( $filterCount == 1 ) {
                            $query->where($column, '<=', $priceRange[0]);
                        }
                    });
                }
            });
        }

    }

    public function scopeFilterProduct($query, $params)
    {
        $query->withoutGlobalScope('order');

        if ( $scope = $this->getFilterOption('scope.product') ){
            $scope($query);
        }

        if (
            $this->getFilterOption('$ignore.filter.attributes', false) == false
            && $filter = $this->getFilterFromQuery($params)
        ) {
            foreach ($filter as $attributeId => $itemIds) {
                $query->filterAttributeItems($itemIds);
            }
        }

        if ( $this->getFilterOption('$ignore.filter.prices', false) == false ) {
            $query->applyPriceRangeFilter($params);
        }
    }

    public function scopeFilterParentProduct($query, $filter = null)
    {
        $query->withoutGlobalScope('order');

        //Apply user filter scope
        if ( $scope = $this->getFilterOption('scope') ){
            $scope($query);
        }

        $filter = $filter ?: $this->getFilterOption('filter', []);

        $query->applyCategoryFilter($filter);
        $query->applySearchFilter($filter);
    }

    public function scopeFilterVariantProduct($query, $options = null)
    {
        //Apply user filter scope on variants
        if ( $scope = $this->getFilterOption('scope.variants') ){
            $scope($query);
        }
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

    /**
     * Extract product variants by according to attribute ids
     *
     * @return  array|integer
     */
    public function getExtractorAttributes()
    {
        // return env('ATTR_COLOR_ID');
    }
}