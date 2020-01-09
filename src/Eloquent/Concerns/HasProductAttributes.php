<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Models\Products\ProductsAttribute;

trait HasProductAttributes
{
    /*
     * Return attributes items
     */
    public function attributesItems()
    {
        $relationModel = (new ProductsAttribute);

        return $this->hasMany(ProductsAttribute::class)
                      ->select($relationModel->fixAmbiguousColumn(['products_variant_id', 'id', 'attribute_id', 'item_id']))
                      ->addSelect(['attributes.unit', 'attributes_items.name as item_name'])
                      ->leftJoin('attributes', 'attributes.id', '=', $relationModel->getTable().'.attribute_id')
                      ->leftJoin('attributes_items', 'attributes_items.id', '=', $relationModel->getTable().'.item_id');
    }

    public function getAttributesTextAttribute()
    {
        $attributes = [];

        foreach ($this->getValue('attributesItems') as $attribute) {
            $attributes[] = $attribute->item_name.$attribute->unit;
        }

        return implode(', ', $attributes);
    }
}