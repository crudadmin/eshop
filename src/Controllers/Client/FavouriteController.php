<?php

namespace AdminEshop\Controllers\Client;

use Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FavouriteController extends Controller
{
    public function index()
    {
        $favourites = Admin::getModel('ClientsFavourite')->activeSession();

        return api([
            'favourites' => $favourites->withFavouriteResponse()->get()->map(function($item){
                return $item->setFavouriteResponse();
            }),
        ]);
    }

    public function toggleFavourite()
    {
        $favouritesModel = Admin::getModel('ClientsFavourite');
        $favourites = $favouritesModel->activeSession();

        $productId = request('product_id');
        $variantId = request('variant_id');

        //Add or update variant
        if ( is_numeric($variantId) ) {
            if ( $favourites->where('variant_id', $variantId)->count() > 0 ) {
                $favourites->where('variant_id', $variantId)->delete();
            } else {
                $favouritesModel->create([
                    'variant_id' => $variantId,
                    'product_id' => $productId,
                ] + $favouritesModel->getFavouritesIdentifiers());
            }
        } else if ( is_numeric($productId) ) {
            if ( $favourites->where('product_id', $productId)->whereNull('variant_id')->count() > 0 ) {
                $favourites->where('product_id', $productId)->whereNull('variant_id')->delete();
            } else {
                $favouritesModel->create([
                    'variant_id' => $variantId,
                    'product_id' => $productId,
                ] + $favouritesModel->getFavouritesIdentifiers());
            }
        }

        return $this->index();
    }
}