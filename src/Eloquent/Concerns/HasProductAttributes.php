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
    public function attributesItemsPivot()
    {
        return $this->hasMany(ProductsAttributesItem::class);
    }

    public function variantsAttributesItemsPivot()
    {
        return $this->hasManyThrough(ProductsAttributesItem::class, Product::class);
    }

    public function getAttributesListAttribute()
    {
        $attributes = [];

        foreach ($this->attributesItems as $item) {
            if ( !$item->attribute ){
                continue;
            }

            if ( !array_key_exists($item->attribute_id, $attributes) ){
                $attributes[$item->attribute_id] = $item->attribute->setRelation('items', collect());
            }

            $attributes[$item->attribute_id]->items[] = $item;
        }

        return collect(array_values($attributes));
    }

    public function getAttributesTextAttribute()
    {
        //TODO
        return;
        //If attributes for given model are not enabled, or fetched by developer
        if ( $this->hasAttributesEnabled() == false || !$this->relationLoaded('attributesItems') ){
            return;
        }

        $attributes = [];

        foreach ($this->attributesItems->groupBy('products_attribute_id') as $attributeItems) {
            $attributes[] = $attributeItems->map(function($item) {
                $attribute = $item->attribute;
                $attrItem = $item->item;

                if ( $attribute && $attribute->displayableInTextAttributes() !== true ){
                    return;
                }

                return ($attrItem ? $attrItem->getAttributeItemValue($attribute) : '').($attribute ? (' '.$attribute->unitName) : '');
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