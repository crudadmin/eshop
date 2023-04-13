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

        //TODO: return also subcategories. Only we have created support for base level of categories
        $categories = Admin::getModel('Category')
                        ->whereNull('category_id')
                        ->withListingResponse($options + [
                            'onlyFilter' => true,
                        ])
                        ->get()
                        ->each->setListingResponse();

        return api(
            $category,
            $this->getListingResponse($options),
            [
                'categories' => $categories,
            ]
        );
    }
}
