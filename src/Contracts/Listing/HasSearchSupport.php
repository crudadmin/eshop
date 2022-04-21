<?php

namespace AdminEshop\Contracts\Listing;

use Illuminate\Support\Facades\Cache;
use Store;
use Admin;

trait HasSearchSupport
{
    protected function getProductsResults($search, $limit = 10)
    {
        if ( !$search ){
            return [];
        }

        $filter = array_merge(request('filter', []), [
            '_search' => $search,
        ]);

        return Admin::getModel('Product')->withSearchResponse([
            'filter' => $filter,
        ])->take(10)->get()->each->setSearchResponse();
    }

    protected function getCategoriesResults($search, $limit = 5)
    {
        if ( !$search ){
            return [];
        }

        return Admin::getModel('Category')
                ->fulltextSearch($search)
                ->take($limit)
                ->get()->each->setSearchResponse();
    }
}
?>