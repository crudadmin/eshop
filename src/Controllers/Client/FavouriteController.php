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

        return client()->favourites()->responseQuery()->get()->map(function($item){
            return $item->toResponseFormat();
        });
    }

    public function toggleFavourite()
    {
        $productId = request('product_id');
        $variantId = request('variant_id');

        //Add or update variant
        if ( is_numeric($variantId) ) {
            if ( client()->favourites()->where('variant_id', $variantId)->count() > 0 ) {
                client()->favourites()->where('variant_id', $variantId)->delete();
            } else {
                client()->favourites()->create([
                    'variant_id' => $variantId,
                    'product_id' => $productId,
                ]);
            }
        } else if ( is_numeric($productId) ) {
            if ( client()->favourites()->where('product_id', $productId)->whereNull('variant_id')->count() > 0 ) {
                client()->favourites()->where('product_id', $productId)->whereNull('variant_id')->delete();
            } else {
                client()->favourites()->create([
                    'variant_id' => $variantId,
                    'product_id' => $productId,
                ]);
            }
        }

        return $this->index();
    }
}