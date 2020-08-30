<?php
namespace AdminEshop\Facades;

use Illuminate\Support\Facades\Facade;

class CartDriverFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cart.driver';
    }
}