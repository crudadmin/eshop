<?php

namespace AdminEshop\Controllers\Product;

use AdminEshop\Controllers\Controller;
use Admin;
use Store;

class ProductController extends Controller
{
    public function show($slug, $variantId = null)
    {
        $categorySlug = request('category');

        $product = Admin::getModel('Product')
                    ->parentProducts()
                    ->when($categorySlug, function($query, $categorySlug){
                        $query->whereHas('categories', function($query) use ($categorySlug) {
                            $query->whereSlug($categorySlug);
                        });
                    })
                    ->withDetailResponse()
                    ->findBySlugOrFail($slug)
                    ->setDetailResponse();

        $response = [];

        if ( Store::hasCategories() ){
            $response['similarProducts'] = $product->getSimilarProducts();
        }

        return api($product, $response);
    }
}
