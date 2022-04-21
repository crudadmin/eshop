<?php

namespace AdminEshop\Controllers;

use Admin;
use AdminEshop\Contracts\Listing\HasSearchSupport;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use HasSearchSupport;

    public function index()
    {
        $search = mb_strtolower(request('query'));

        $products = $this->getProductsResults($search);
        $categories = $this->getCategoriesResults($search);

        return api([
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
