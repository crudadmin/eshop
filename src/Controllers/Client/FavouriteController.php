<?php

namespace AdminEshop\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FavouriteController extends Controller
{
    public function index()
    {
        if ( !client() ){
            return collect([]);
        }

        return api([
            'favourites' => client()->favourites()->withFavouriteResponse()->get()->map(function($item){
                return $item->setFavouriteResponse();
            }),
        ]);
    }

    public function toggleFavourite()
    {
        $productId = request('product_id');

        $favourites = client()->favourites();

        //Add or update variant
        if ( is_numeric($productId) ) {
            if ( $favourites->where('product_id', $productId)->count() > 0 ) {
                $favourites->where('product_id', $productId)->delete();
            } else {
                $favourites->create([
                    'product_id' => $productId,
                ]);
            }
        }

        return $this->index();
    }
}