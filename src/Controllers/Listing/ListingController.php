<?php

namespace AdminEshop\Controllers\Listing;

use AdminEshop\Contracts\Listing\HasListingSupport;
use AdminEshop\Controllers\Controller;
use Admin;

class ListingController extends Controller
{
    use HasListingSupport;

    public function index($categorySlug = null)
    {
        $category = null;

        $options = [
            'filter' => $this->getFilter(),
        ];

        if ( $categorySlug ){
            $category = Admin::getModel('Category')->findBySlugOrFail($categorySlug)->setListingResponse();

            $options['filter']['_categories'] = $category->getKey();
        }

        return api(
            $category,
            $this->getListingResponse($options)
        );
    }
}
