<?php

namespace AdminEshop\Eloquent\Paginator;

use Illuminate\Pagination\LengthAwarePaginator;
use Store;

class ProductsPaginator extends LengthAwarePaginator
{
    protected $pushIntoArray = [];

    public $onEachSide = 1;

    public function toArray()
    {
        $array = parent::toArray();

        return array_merge($array, $this->pushIntoArray);
    }
}