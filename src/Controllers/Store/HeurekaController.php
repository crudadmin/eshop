<?php

namespace AdminEshop\Controllers\Store;

use AdminEshop\Contracts\Heureka\HeurekaBuilder;
use AdminEshop\Controllers\Controller;
use Admin;
use Cache;

class HeurekaController extends Controller
{
    public function index()
    {
        ini_set('max_execution_time', 300);

        $callback = function(){
            $builder = new HeurekaBuilder;

            $deliveries = Admin::getModel('Delivery')->whereNotNull('heureka_id')->get();

            return view('admineshop::xml.heureka', compact('builder', 'deliveries'))->render();
        };

        if ( env('APP_DEBUG') == true ) {
            $xml = $callback();
        } else {
            $xml = Cache::remember('heureka.feed', 600, $callback);
        }

        return response($xml, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }
}
