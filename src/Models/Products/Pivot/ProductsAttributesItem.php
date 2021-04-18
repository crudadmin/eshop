<?php

namespace AdminEshop\Models\Products\Pivot;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Store;

class ProductsAttributesItem extends Pivot
{
    protected $table = 'attributes_item_products_attribute_items';

    public function getItemRow()
    {
        return Store::getAttributeItem($this->attributes_item_id);
    }

    public function getAttributeRow()
    {
        return Store::getAttribute($this->attribute_id);
    }
}
