<?php

namespace AdminEshop\Models\Attribute\Pivot;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Store;

class AssignedProductsPivot extends Pivot
{
    protected $table = 'attributes_item_products_attribute_items';
}
