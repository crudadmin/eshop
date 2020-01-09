<?php

namespace AdminEshop\Controllers\Basket;

use Illuminate\Http\Request;
use \AdminEshop\Controllers\Controller;
use \AdminEshop\Models\Products\Product;
use \AdminEshop\Models\Store\Store;
use \AdminEshop\Models\Store\Country;
use Basket;
use Admin;

class BasketController extends Controller
{
    public function addItem()
    {
        $product = Admin::getModelByTable('products')
                        ->select(['id'])
                        ->where('id', request('product_id'))
                        ->onStock()
                        ->firstOrFail();

        $items = Basket::add($product->getKey(), request('quantity'), request('variant_id'));

        return $items->all();
    }
}
