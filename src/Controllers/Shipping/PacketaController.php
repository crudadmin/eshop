<?php

namespace AdminEshop\Controllers\Shipping;

use Admin\Controllers\Controller;
use OrderService;
use Cart;

class PacketaController extends Controller
{
    public function setPoint()
    {
        $point = request('point');

        OrderService::getPacketaMutator()->setSelectedPoint($point);

        return Cart::fullCartResponse();
    }
}