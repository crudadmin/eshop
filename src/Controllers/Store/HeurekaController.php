<?php

namespace AdminEshop\Controllers\Store;

use AdminEshop\Contracts\Heureka\HeurekaBuilder;
use AdminEshop\Controllers\Controller;
use Admin;

class HeurekaController extends Controller
{
    public function index()
    {
        $builder = new HeurekaBuilder;

        $deliveries = Admin::getModel('Delivery')->get();

        return view('admineshop::xml.heureka', compact('builder', 'deliveries'));
    }
}
