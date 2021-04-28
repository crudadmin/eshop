<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Eloquent\Concerns\HasAttributesSupport;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;
use Admin\Eloquent\Modules\SeoModule;
use Store;

trait HasProductResponses
{
    /**
     * This columns are shared between product an variant.
     * You can replace this method in model.
     *
     * @return  array
     */
    public function visibleOrderableColumns()
    {
        $columns = [
            'id', 'slug', 'name', 'stock_type',
        ];

        //For variants we does not need this properties, because they are present in each variant
        if ( in_array($this->getAttribute('product_type'), Store::variantsProductTypes()) === false ){
            $columns = array_merge($columns, ['initialPriceWithVat', 'priceWithVat', 'priceWithoutVat']);
            $columns = array_merge($columns, ['stock_quantity', 'stockText', 'stockNumber', 'hasStock',]);
        }

        $columns = array_merge($columns, array_filter([
            'thumbnail',
            'attributesText',
        ]));

        return $columns;
    }

    public function setCategoryResponse()
    {
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

        if ( $this->hasGalleryEnabled() ) {
            $this->gallery->each->setDetailResponse();

            $this->makeVisible(['gallery']);
        }

        if ( $this->hasAttributesEnabled() ) {
            $this->makeVisible(['attributesList']);
            $this->append(['attributesList']);

            $this->attributesList->each->append([
                'unitName',
            ]);
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
            'attributesText'
        ]);

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
     */
    public function scopeWithCategoryResponse($query, $filterParams = [])
    {
        $query->applyQueryFilter($filterParams);

        if ( $this->hasAttributesEnabled() ) {
            $query->with([
                'attributesItems',
            ]);
        }

        if ( $this instanceof Product && count(Store::variantsProductTypes()) ){
            $query->with(['variants' => function($query) use ($filterParams) {
                $query->withParentProductData();

                //We can deside if filter should be applied also on selected variants
                if ( Admin::getModel('ProductsVariant')->getProperty('applyFilterOnVariants') == true ) {
                    $query->applyQueryFilter($filterParams);
                }

                if ( $query->getModel()->hasAttributesEnabled() ) {
                    $query->with(['attributesItems']);
                }
            }]);
        }
    }

    public function scopeWithDetailResponse($query)
    {
        if ( $this->hasGalleryEnabled() ) {
            $query->with(['gallery']);
        }

        if ( Admin::getModel('ProductsVariant')->hasGalleryEnabled() ) {
            $query->with(['variants.gallery']);
        }
    }

    public function scopeWithCartResponse($query)
    {
        $query->select($this->getCartSelectColumns());

        //Add attributes support into cart
        if (
            config('admineshop.attributes.load_in_cart') === true
            && $query->getModel() instanceof HasAttributesSupport
            && $query->getModel()->hasAttributesEnabled()
        ) {
            $query->with(['attributesItems']);
        }
    }

    public function scopeGetPriceRange($query, $filterParams)
    {
        $products = $query
                        ->select(...$this->getPriceSelectColumns())
                        ->addSelect('product_type', 'id')
                        ->applyQueryFilter($filterParams);

        if ( count(Store::variantsProductTypes()) ) {
            $products->with([
                'variants' => function($query) use ($filterParams){
                    $query->select(...$query->getModel()->getPriceSelectColumns())->addSelect('product_id')->withoutGlobalScope('order');

                    //We can deside if filter should be applied also on selected variants
                    if ( Admin::getModel('ProductsVariant')->getProperty('applyFilterOnVariants') == true ) {
                        $query->applyQueryFilter($filterParams);
                    }
                }
            ]);
        }

        $prices = $products->get()->map(function($product){
            if ( in_array($product->product_type, Store::variantsProductTypes()) ){
                return $product->cheapestVariantClientPrice;
            } else {
                return $product->clientPrice;
            }
        })->sort()->values();

        return [
            $prices->first() ?: 0,
            $prices->last() ?: 0
        ];
    }
}