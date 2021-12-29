<?php

namespace AdminEshop\Controllers\Order;

use AdminEshop\Controllers\Controller;

class OrderController extends Controller
{
    public function index()
    {
        $orders = client()->orders()
                        ->with('invoices:id,order_id,pdf')
                        ->withClientListingResponse()
                        ->paginate(request('limit', 10));

        $orders->getCollection()->each(function($order){
            return $order->makeHidden('invoices')->setClientListingResponse();
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
                        ->setClientListingResponse();

        return api([
            'order' => $order,
            'items' => $order->items->map(function($item){
                return $item->setClientListingResponse();
            }),
        ]);
    }
}
