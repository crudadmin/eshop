<?php
namespace AdminEshop\Facades;

use Illuminate\Support\Facades\Facade;

class StoreFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'store';
    }
}