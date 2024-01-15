<?php

namespace AdminEshop\Controllers\Order;

use AdminEshop\Controllers\Controller;
use Cart;

class OrderController extends Controller
{
    public function index()
    {
        $orders = client()->orders()
                        ->with([
                            'invoices',
                            'status',
                        ])
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
                        ->findOrFail($id);

        return api([
            'order' => $order->setClientListingResponse(),
            'items' => $order->items->map(function($item){
                return $item->setClientListingResponse();
            }),
        ]);
    }

    public function repeat($id)
    {
        $order = client()->orders()->where('id', $id)->with('items')->get()->first();

        //Add orderItems into cart
        foreach ($order->items as $item) {
            $identifier = $item->getIdentifierClass();

            Cart::addOrUpdate(
                $identifier,
                $item->quantity
            );
        }

        return autoAjax()
                ->success(_('Položky z objednávky boli pridané do košíka.'))
                ->data([
                    'cart' => Cart::response(),
                ]);
    }
}
