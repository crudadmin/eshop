<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;
use Store;
use Admin;

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
        return [
            'id', 'slug', 'name',
            'initialPriceWithVat', 'priceWithVat', 'priceWithoutVat',
            'stock_quantity', 'stock_type', 'stockText', 'stockNumber', 'hasStock',
            'thumbnail', 'attributesText', 'attributes',
        ];
    }

    /**
     * Which columns should be appended
     * You can replace this method in your model
     *
     * @return  array
     */
    public function appendOrderableColumns()
    {
        return [
            'thumbnail',
        ];
    }

    public function setCategoryResponse()
    {
        $this->setVisible(
            $this->visibleOrderableColumns()
        );

        $this->append($this->appendOrderableColumns());

        $this->mutateCategoryResponse();

        return $this;
    }

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


        $this->makeVisible([
            'categories',
            'detailThumbnail',
            'code',
        ]);

        $this->append([
            'detailThumbnail',
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

    public function scopewithDetailResponse($query)
    {
        if ( $this->hasGalleryEnabled() ) {
            $query->with(['gallery']);
        }

        $query->with(['variants.gallery']);
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