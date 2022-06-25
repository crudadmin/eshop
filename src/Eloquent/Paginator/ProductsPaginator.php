<?php

namespace AdminEshop\Eloquent\Paginator;

use AdminEshop\Contracts\Listing\UrlWindow;
use Illuminate\Pagination\LengthAwarePaginator;
use Store;

class ProductsPaginator extends LengthAwarePaginator
{
    protected $pushIntoArray = [];

    public $onEachSide = 2;

    public function toArray()
    {
        $array = parent::toArray();

        return array_merge($array, $this->pushIntoArray);
    }

    /**
     * Get the array of elements to pass to the view.
     *
     * @return array
     */
    protected function elements()
    {
        $window = UrlWindow::make($this);

        return array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);
    }
}