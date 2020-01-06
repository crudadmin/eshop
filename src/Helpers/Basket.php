<?php

namespace AdminEshop\Helpers;

use \Illuminate\Database\Eloquent\Collection;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Orders\OrdersProduct;
use AdminEshop\Models\Store\Country;
use DB;
use Store;

class Basket
{
    public function getCurrency()
    {
        return '€';
    }
}

?>