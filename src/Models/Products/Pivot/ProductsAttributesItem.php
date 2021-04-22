<?php

namespace AdminEshop\Models\Products\Pivot;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Store;

class ProductsAttributesItem extends Pivot
{
    protected $table = 'attributes_item_products_attribute_items';

    public function getItemAttribute()
    {
        if ( $this->relationLoaded('getItemAttribute') ) {
            return $this->getRelation('getItemAttribute');
        }

        if ( $item = Store::getAttributeItem($this->attributes_item_id) ){
            $item = clone $item;
        }

        return $this->setRelation('getItemAttribute', $item);
    }

    public function getAttributeAttribute()
    {
        if ( $this->relationLoaded('getAttributeAttribute') ) {
            return $this->getRelation('getAttributeAttribute');
        }

        if ( $attribute = Store::getAttribute($this->attribute_id) ){
            $attribute = clone $attribute;
        }

        return $this->setRelation('getAttributeAttribute', $attribute);
    }
}
