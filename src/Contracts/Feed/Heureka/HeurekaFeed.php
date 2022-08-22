<?php

namespace AdminEshop\Contracts\Feed\Heureka;

use AdminEshop\Contracts\Feed\Feed;
use Admin;

class HeurekaFeed extends Feed
{
    public $contentType = 'application/xml';

    public function enabled()
    {
        return config('admineshop.heureka.enabled', false);
    }

    public function data()
    {
        $deliveries = Admin::getModel('Delivery')->whereNotNull('heureka_id')->get();

        return view('admineshop::xml.heureka', compact('deliveries') + [
            'feed' => $this,
        ])->render();
    }
}
?>