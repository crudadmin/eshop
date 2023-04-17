<?php

namespace AdminEshop\Controllers\Product;

use AdminEshop\Controllers\Controller;
use Admin;

class ProductController extends Controller
{
    public function show($slug, $variantId = null)
    {
        $product = Admin::getModel('Product')
                    ->parentProducts()
                    ->withDetailResponse()
                    ->findBySlugOrFail($slug)
                    ->setDetailResponse();

        return api(
            $product,
            [
                'similarProducts' => $product->getSimilarProducts(),
            ]
        );
    }
}
