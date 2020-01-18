<?php

namespace AdminEshop\Admin\Rules;

use Admin\Eloquent\AdminRule;

class RebuildOrderOnItemChange extends AdminRule
{
    public function created($row)
    {
        if ( $order = $row->order ) {
            $order->calculatePrices();
        }
    }

    public function updated($row)
    {
        if ( $order = $row->order ) {
            $order->calculatePrices($row);
        }
    }

    public function deleted($row)
    {
        if ( $order = $row->order ) {
            $order->calculatePrices();
        }
    }
}