<?php
namespace AdminEshop\Facades;

use Illuminate\Support\Facades\Facade;

class OrderServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'order.service';
    }
}