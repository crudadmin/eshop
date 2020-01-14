<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class ReloadProductQuantity  extends AdminRule
{
    public function update($row)
    {
        if ( $order = $row->order ) {
            $order->calculatePrices();
        }
    }

    public function create($row)
    {
        if ( $order = $row->order ) {
            $order->calculatePrices();
        }
    }

    public function delete($row)
    {
        if ( $order = $row->order ) {
            $order->calculatePrices();
        }
    }
}