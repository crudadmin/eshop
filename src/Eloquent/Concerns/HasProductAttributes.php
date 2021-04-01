<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsAttribute;
use AdminEshop\Models\Store\AttributesItem;

trait HasProductAttributes
{
    public function getAttributesSelect()
    {
        $columns = [
            'products_attributes.id',
            'products_attributes.attribute_id',
        ];

        if ( $this->hasAttributesEnabled(ProductsVariant::class) ){
            $columns[] = 'products_attributes.products_variant_id';
        }

        if ( $this->hasAttributesEnabled(Product::class) ){
            $columns[] = 'products_attributes.product_id';
        }

        return array_merge(
            Admin::getModel('Attribute')->getProductAttributesColumns(),
            $columns
        );
    }

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

        return $this
                ->hasMany($relationClass)
                ->select($this->getAttributesSelect())
                ->leftJoin('attributes', 'attributes.id', '=', 'products_attributes.attribute_id')
                ->with(['items' => function($query){
                    $query->select(
                        $this->getAttributesItemsSelect()
                    );
                }]);
    }

    public function getAttributesTextAttribute()
    {
        //If attributes for given model are not enabled
        if ( $this->hasAttributesEnabled() == false ){
            return;
        }

        $attributes = [];

        foreach ($this->getValue('attributesItems') as $attribute) {
            if ( $items = $attribute->getAttributesTextItems() ) {
                $attributes[] = $items->map(function($item) use ($attribute) {
                    return $item->name.$attribute->unitName;
                })->join(config('admineshop.attributes.separator.item', ', '));
            }
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