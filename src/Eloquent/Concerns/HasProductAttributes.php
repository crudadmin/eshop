<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Models\Attribute\AttributesItem;
use AdminEshop\Models\Products\Pivot\ProductsAttributesItem;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsAttribute;

trait HasProductAttributes
{
    public function getAttributesItemsSelect()
    {
        return Admin::getModel('AttributesItem')->getProductAttributesItemsColumns();
    }

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
                    ->whereNull('products_attributes.deleted_at')
                    ->whereNull('attributes_items.deleted_at');
                ;
    }

    public function getAttributesTextAttribute()
    {
        //If attributes for given model are not enabled
        if ( $this->hasAttributesEnabled() == false ){
            return;
        }

        $attributes = [];

        foreach ($this->attributesItems->groupBy('products_attribute_id') as $attributeItems) {
            $attributes[] = $attributeItems->map(function($item) {
                $attribute = $item->getAttributeRow();
                $attrItem = $item->getItemRow();

                return ($attrItem ? $attrItem->name : '').($attribute ? $attribute->unitName : '');
            })->join(config('admineshop.attributes.separator.item', ', '));
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