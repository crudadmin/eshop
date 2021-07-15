<?php

namespace AdminEshop\Contracts\Delivery;

use AdminEshop\Contracts\Order\OrderProvider;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Orders\Order;
use Illuminate\Support\Collection;

class ShippingProvider extends OrderProvider
{
    /**
     * Admin order buttons
     *
     * @return  array
     */
    public function buttons()
    {
        return [];
    }

    public function getKey()
    {
        return class_basename($this);
    }

    /*
     * Has shipping export?
     */
    public function isExportable()
    {
        return false;
    }

    public static function export(Collection $orders)
    {
        // return 'string';
    }
}