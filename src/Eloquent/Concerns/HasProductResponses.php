<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Eloquent\Concerns\HasAttributesSupport;
use AdminEshop\Models\Products\Product;
use Admin\Eloquent\Modules\SeoModule;
use Illuminate\Support\Facades\DB;
use Store;

trait HasProductResponses
{
    /**
     * Which price fields should be exposed into request
     *
     * @return  array
     */
    public function getVisiblePriceAttributes()
    {
        return ['initialPriceWithVat', 'defaultPriceWithVat', 'priceWithVat', 'priceWithoutVat'];
    }

    /**
     * Which stock attributes should be exposed into request
     *
     * @return  array
     */
    public function getVisibleStockAttributes()
    {
        $columns = ['stock_quantity', 'stockText', 'stockNumber', 'hasStock'];

        if ( config('admineshop.stock.store_rules', true) ) {
            $columns[] = 'canOrderEverytime';
        }

        return $columns;
    }

    /**
     * This columns are shared between product an variant.
     * You can replace this method in model.
     *
     * @return  array
     */
    public function visibleOrderableColumns()
    {
        $columns = ['id', 'slug', 'name', 'thumbnail'];

        if ( config('admineshop.stock.store_rules', true) ) {
            $columns[] = 'stock_type';
        }

        if ( config('admineshop.attributes.attributesText', false) == true ) {
            $columns[] = 'attributesText';
        }

        //In main product we does not need this properties, because they are present in each variant
        if ( in_array($this->getAttribute('product_type'), Store::variantsProductTypes()) === false ){
            $columns = array_merge($columns, $this->getVisiblePriceAttributes());
            $columns = array_merge($columns, $this->getVisibleStockAttributes());
        }

        return $columns;
    }

    public function setCategoryResponse()
    {
        $this->append($this->getPriceAttributes());

        $this->setVisible(
            $this->visibleOrderableColumns()
        );

        $this->append([
            'thumbnail',
        ]);

        $this->mutateCategoryResponse();

        return $this;
    }

    /**
     * Which columns should be appended
     * You can replace this method in your model
     *
     * @return  array
     */
    public function setDetailResponse()
    {
        $this->setCategoryResponse();

        $this->mutateDetailResponse();

        if ( $this->hasGalleryEnabled() && $this->relationLoaded('gallery') ) {
            $this->gallery->each->setDetailResponse();

            $this->makeVisible(['gallery']);
        }

        if ( $this->relationLoaded('attributesItems') ) {
            $this->makeVisible(['attributesList']);
            $this->append(['attributesList']);

            $this->attributesList->each->setDetailResponse();
        }

        if ( $this->getProperty('seo') == true ){
            $this->makeVisible([
                ...SeoModule::$metaKeys,
                'metaImageThumbnail',
            ]);

            $this->append([
                'metaImageThumbnail',
            ]);
        }


        $this->makeVisible([
            'description',
            'categories',
            'detailThumbnail',
            'code',
        ]);

        $this->append([
            'detailThumbnail',
        ]);

        if ( config('admineshop.attributes.attributesText', false) == true ) {
            $this->append(['attributesText']);
        }

        return $this;
    }

    /**
     * We can set cart response... we can append() or make hidden fields in this method here
     *
     * void
     */
    public function setCartResponse()
    {
        $this->setCategoryResponse();

        return $this;
    }

    public function mutateCategoryResponse()
    {

    }

    public function mutateDetailResponse()
    {

    }

    /**
     * Display products into category response
     * Also filter products and variants
     *
     * @param  Builder  $query
     * @param  array  $filterParams
     * @param  bool|array  $extractVariants
     */
    public function scopeWithCategoryResponse($query, $filterParams = [], $extractVariants = false)
    {
        //We need specify select for
        $query->addSelect('products.*');

        $query->applyQueryFilter($filterParams, $extractVariants);

        $query->withMainGalleryImage();

        $query->sortByParams($filterParams, $extractVariants);

        if ( $this->mainProductAttributes === true ) {
            $query->with([
                'attributesItems',
            ]);
        }

        if ( $extractVariants === false && $this->loadVariants == true && count(Store::variantsProductTypes()) ){
            $query->extendWith(['variants' => function($query) use ($filterParams) {
                $model = $query->getModel();

                $query->withParentProductData();

                $query->withMainGalleryImage(true);

                //We can deside if filter should be applied also on selected variants
                if ( $model->applyFilterOnVariants == true ) {
                    $query->filterProduct($filterParams);
                }

                if ( $model->variantsAttributes ) {
                    $query->with(['attributesItems']);
                }
            }]);
        }
    }

    public function scopeWithDetailResponse($query)
    {
        if ( $this->mainProductGallery ) {
            $query->with(['gallery']);
        }

        //Extend variants with gallery
        //We need rewrite eagerLoads and keep existing variants scope if is present
        //because if withCategoryResponse has been called before, we will throw all nested withs if we would
        //call simple with("variants.gallery")
        if ( $this->variantsGallery ) {
            $query->extendWith([
                'variants' => function($query) {
                    $query->with('gallery');
                },
            ]);
        }
    }

    public function scopeWithCartResponse($query)
    {
        $query->withCategoryResponse();

        $query->select($this->getCartSelectColumns());

        //If variants are not enabled in cart response, we need throw away relation
        $variantsIntoCart = array_filter(Store::variantsProductTypes(), function($key){
            return config('admineshop.product_types.'.$key.'.loadInCart', false) == true;
        });
        if ( count($variantsIntoCart) == 0 ){
            $query->without('variants');
        }
    }

    public function scopeSortByParams($query, $filterParams, $extractVariants = false)
    {
        if ( !($sortBy = $filterParams['_sort'] ?? null) ){
            return;
        }

        if ( in_array($sortBy, ['expensive', 'cheapest', 'latest']) ) {
            $expensive = $sortBy == 'expensive';
            $latest = $sortBy == 'latest';
            $agregatedColumn = $latest == true ? 'products.id' : 'products.price';

            $isDesc = $expensive || $latest;

            //Enabled variant extraction
            if ( $extractVariants === false ) {
                $variantsPrices = DB::table('products')
                                    ->selectRaw(($isDesc ? 'max' : 'min').'('.$agregatedColumn.') as aggregator, product_id')
                                    ->where('product_type', 'variant')
                                    ->groupBy('product_id');

                $query
                    ->leftJoinSub($variantsPrices, 'pricedVariants', function($join){
                        $join->on('products.id', '=', 'pricedVariants.product_id');
                    })
                    ->addSelect(DB::raw('IFNULL(pricedVariants.aggregator, '.$agregatedColumn.') as aggregator'));
            } else {
                $query->addSelect(DB::raw($agregatedColumn.' as aggregator'));
            }

            $query->orderBy('aggregator', $isDesc ? 'DESC' : 'ASC');
        }
    }
}