<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Models\Products\ProductsAttribute;
use AdminEshop\Models\Store\AttributesItem;
use Admin;

trait HasProductAttributes
{
    public function getAttributesSelect()
    {
        $columns = [
            'products_attributes.id',
            'products_attributes.attribute_id',
        ];

        if ( config('admineshop.attributes.variants') === true ){
            $columns[] = 'products_attributes.products_variant_id';
        }

        if ( config('admineshop.attributes.products') === true ){
            $columns[] = 'products_attributes.product_id';
        }

        return array_merge(
            [
                'attributes.name',
                'attributes.unit',
            ],
            $columns
        );
    }

    public function getAttributesItemsSelect($query)
    {
        return [
            'attributes_items.id',
            'attributes_items.attribute_id',
            'attributes_items.name',
            'attributes_items.slug',
        ];
    }

    /*
     * Return attributes items
     */
    public function attributesItems()
    {
        //We need return not model from package, but end model which may extend features
        $relationClass = get_class(Admin::getModelByTable('products_attributes'));

        return $this
                ->hasMany($relationClass)
                ->select($this->getAttributesSelect())
                ->leftJoin('attributes', 'attributes.id', '=', 'products_attributes.attribute_id')
                ->with(['items' => function($query){
                    $query->select($this->getAttributesItemsSelect($query));
                }]);
    }

    public function getAttributesTextAttribute()
    {
        $attributes = [];

        foreach ($this->getValue('attributesItems') as $attribute) {
            if ( $items = $attribute->getAttributesTextItems() ) {
                $attributes[] = $items->map(function($item) use ($attribute) {
                    return $item->name.$attribute->unit;
                })->join(config('admineshop.attributes.separator.item', ', '));
            }
        }

        return implode(config('admineshop.attributes.separator.attribute', ', '), $attributes);
    }
}