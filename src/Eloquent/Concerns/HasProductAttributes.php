<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Models\Attribute\AttributesItem;
use AdminEshop\Models\Products\Pivot\ProductsAttributesItem;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsAttribute;

trait HasProductAttributes
{
    /*
     * Return attributes items
     */
    public function attributesItems()
    {
        //We need return not model from package, but end model which may extend features
        $relationClass = get_class(Admin::getModel('ProductsAttribute'));

        return $this->hasManyThrough(ProductsAttributesItem::class, $relationClass)
                    ->leftJoin('attributes_items', 'attributes_items.id', '=', 'attributes_item_products_attribute_items.attributes_item_id')
                    ->select(
                        'products_attributes.attribute_id',
                        'attributes_item_products_attribute_items.attributes_item_id'
                    )
                    ->whereNull('attributes_items.deleted_at');
    }

    public function getAttributesListAttribute()
    {
        $attributes = [];

        foreach ($this->attributesItems as $item) {
            if ( !$item->attribute || !$item->item ){
                continue;
            }

            if ( !array_key_exists($item->attribute_id, $attributes) ){
                $attributes[$item->attribute_id] = $item->attribute->setRelation('items', collect());
            }

            $attributes[$item->attribute_id]->items[] = $item->item;
        }

        return collect(array_values($attributes));
    }

    public function getAttributesTextAttribute()
    {
        //If attributes for given model are not enabled, or fetched by developer
        if ( $this->hasAttributesEnabled() == false || !$this->relationLoaded('attributesItems') ){
            return;
        }

        $attributes = [];

        $grouppedAttributes = $this->attributesItems->sortBy(function($item){
            return $item->attribute?->getAttribute('_order');
        })->groupBy('products_attribute_id');

        foreach ($grouppedAttributes as $attributeItems) {
            $attributes[] = $attributeItems->map(function($item) {
                $attribute = $item->attribute;
                $attrItem = $item->item;

                if ( $attribute && $attribute->displayableInTextAttributes() !== true ){
                    return;
                }

                return ($attrItem ? $attrItem->getAttributeItemValue($attribute) : '').($attribute ? (($attribute->hasUnitSpace ? ' ' : '').$attribute->unitName) : '');
            })->filter()->join(config('admineshop.attributes.separator.item', ', '));
        }

        return implode(config('admineshop.attributes.separator.attribute', ', '), $attributes);
    }

    public function getAttributesVariantsTextAttribute()
    {
        //If attributes for given model are not enabled, or fetched by developer
        if ( $this->hasAttributesEnabled() == false || !$this->relationLoaded('attributesItems') ){
            return;
        }

        $attributes = [];

        foreach ($this->attributesItems->groupBy('products_attribute_id') as $attributeItems) {
            $attributes[] = $attributeItems->map(function($item) {
                $attribute = $item->attribute;
                $attrItem = $item->item;

                if ( $attribute && $attribute->displayableInVariantsTextAttributes() !== true ){
                    return;
                }

                return ($attrItem ? $attrItem->getAttributeItemValue($attribute) : '').($attribute ? (' '.$attribute->unitName) : '');
            })->filter()->join(config('admineshop.attributes.separator.item', ', '));
        }

        return implode(config('admineshop.attributes.separator.attribute', ', '), $attributes);
    }

    /**
     * Check if given class is enabled
     *
     * @param  string|null  $classname
     *
     * @return  bool
     */
    public function hasAttributesEnabled(string $classname = null)
    {
        $classname = $classname ?: get_class($this);

        $enabledClasses = Admin::cache('store.enabledAttributes', function(){
            return array_map(function($classname){
                return class_basename($classname);
            }, config('admineshop.attributes.eloquents', []));
        });

        //Check if given class has enabled attributes support
        return in_array(class_basename($classname), $enabledClasses);
    }
}