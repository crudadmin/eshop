<?php

namespace AdminEshop\Controllers\Order;

use AdminEshop\Controllers\Controller;

class OrderController extends Controller
{
    public function userOrders()
    {
        $orders = client()->orders()
                        ->with('invoices:id,order_id,pdf')
                        ->get()->map(function($order){
                            return $order->makeHidden('invoices')->toResponseFormat();
                        });

        return [
            'orders' => $orders,
        ];
    }

    public function show($id)
    {
        $order = client()->orders()
                        ->orderDetail()
                        ->findOrFail($id)
                        ->toResponseFormat();

        return [
            'order' => $order,
            'items' => $order->items->map(function($item){
                return $item->toResponseFormat();
            }),
        ];
    }
}
