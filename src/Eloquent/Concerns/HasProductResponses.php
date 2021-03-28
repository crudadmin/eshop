<?php

namespace AdminEshop\Eloquent\Concerns;

trait HasProductResponses
{
    public function setCategoryResponse()
    {
        $this->setVisible([
                'id',
                'slug',
                'name',
                'product_type',
                'attributes',
                'initialPriceWithVat',
                'priceWithVat',
                'priceWithoutVat',
                'stock_quantity',
                'stock_type',
                'stockText',
                'stockNumber',
                'hasStock',
                'thumbnail',
                'attributesText',
            ]);

        $this->append([
            'thumbnail',
            'attributesText',
        ]);

        return $this;
    }
}