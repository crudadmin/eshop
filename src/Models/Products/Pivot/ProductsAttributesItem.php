<?php

namespace AdminEshop\Models\Products\Pivot;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Store;

class ProductsAttributesItem extends Pivot
{
    protected $table = 'attributes_item_products_attribute_items';

    public function getItemAttribute()
    {
        if ( $this->relationLoaded('_attribute_item') ) {
            return $this->getRelation('_attribute_item');
        }

        if ( $item = Store::getAttributeItem($this->attributes_item_id) ){
            $item = clone $item;
        }

        $this->setRelation('_attribute_item', $item);

        return $item;
    }

    public function getAttributeAttribute()
    {
        if ( $this->relationLoaded('_attribute') ) {
            return $this->getRelation('_attribute');
        }

        if ( $attribute = Store::getAttribute($this->attribute_id) ){
            $attribute = clone $attribute;
        }

        $this->setRelation('_attribute', $attribute);

        return $attribute;
    }
}
