<?php
namespace AdminEshop\Facades;

use Illuminate\Support\Facades\Facade;

class Discounts extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'store.discounts';
    }
}