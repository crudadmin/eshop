<?php

namespace AdminEshop\Controllers\Store;

use AdminEshop\Contracts\Heureka\HeurekaBuilder;
use AdminEshop\Controllers\Controller;
use Admin;

class HeurekaController extends Controller
{
    public function index()
    {
        ini_set('max_execution_time', 300);

        $builder = new HeurekaBuilder;

        $deliveries = Admin::getModel('Delivery')->get();

        $xml = view('admineshop::xml.heureka', compact('builder', 'deliveries'))->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }
}
