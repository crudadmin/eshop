<?php

namespace AdminEshop\Controllers\Order;

use AdminEshop\Controllers\Controller;

class OrderController extends Controller
{
    public function userOrders()
    {
        $orders = client()->orders()
                        ->with('invoices:id,order_id,pdf')
                        ->paginate(request('limit', 10));

        $orders->getCollection()->each(function($order){
            return $order->makeHidden('invoices')->toResponseFormat();
        });

        return api([
            'orders' => $orders,
        ]);
    }

    public function show($id)
    {
        $order = client()->orders()
                        ->orderDetail()
                        ->findOrFail($id)
                        ->toResponseFormat();

        return api([
            'order' => $order,
            'items' => $order->items->map(function($item){
                return $item->toResponseFormat();
            }),
        ]);
    }
}
