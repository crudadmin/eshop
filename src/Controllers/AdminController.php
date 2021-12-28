<?php

namespace AdminEshop\Controllers;

use Admin;
use Illuminate\Http\Request;
use Store;

class AdminController extends Controller
{
    public function getOrderItems($id)
    {
        $order = Admin::getModel('Order')->select('id')->with('items')->findOrFail($id);

        return $order->items->map(function($item){
            return [
                'quantity' => $item->quantity,
                'name' => $item->getProductName(),
                'price_vat' => Store::priceFormat($item->price_vat),
                'price_total_vat' => Store::priceFormat($item->price_vat * $item->quantity),
            ];
        });
    }
}
