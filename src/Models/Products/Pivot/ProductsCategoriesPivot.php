<?php

namespace AdminEshop\Models\Products\Pivot;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductsCategoriesPivot extends Pivot
{
    protected $table = 'category_product_categories';
}
