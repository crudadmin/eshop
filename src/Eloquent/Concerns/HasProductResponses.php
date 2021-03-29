<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;

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
            'thumbnail', 'attributesText',
        ];
    }

    public function mutateCategoryResponse()
    {

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

    public function scopeWithCategoryResponse($query)
    {
        $query->with([
            'attributesItems',
        ]);

        if ( $this instanceof Product ){
            $query->with(['variants']);
        }
    }
}