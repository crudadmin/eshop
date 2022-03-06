<?php

namespace AdminEshop\Controllers\Client;

use Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FavouriteController extends Controller
{
    public function index()
    {
        $favourites = Admin::getModel('ClientsFavourite')
                        ->activeSession()
                        ->withFavouriteResponse()
                        ->get();

        $products = Admin::getModel('Product')
                        ->withFavouriteResponse([
                            'scope' => function($query) use ($favourites) {
                                $query->whereIn(
                                    $query->getModel()->getTable().'.id',
                                    $favourites->pluck('product_id')->unique()->filter()->toArray()
                                );
                            },
                            'scope.variants' => function($query) use ($favourites) {
                                $query->whereIn(
                                    $query->getModel()->getTable().'.id',
                                    $favourites->pluck('variant_id')->unique()->filter()->toArray()
                                );
                            }
                        ])
                        ->productsPaginate(
                            request('limit')
                        );

        $products->getCollection()->each->setListingResponse();

        return api([
            'pagination' => $products,
            'favourites' => $favourites->each->setFavouriteResponse(),
        ]);
    }

    public function toggleFavourite()
    {
        $favouritesModel = Admin::getModel('ClientsFavourite');
        $favourites = $favouritesModel->activeSession();

        $productId = request('product_id');
        $variantId = request('variant_id');

        $data = $favouritesModel->getRequestMutator([
            'variant_id' => $variantId,
            'product_id' => $productId,
        ] + $favouritesModel->getFavouritesIdentifiers());

        //Add or update variant
        if ( is_numeric($variantId) ) {
            if ( $favourites->where('variant_id', $variantId)->count() > 0 ) {
                $favourites->where('variant_id', $variantId)->delete();
            } else {
                $favouritesModel->create($data);
            }
        } else if ( is_numeric($productId) ) {
            if ( $favourites->where('product_id', $productId)->whereNull('variant_id')->count() > 0 ) {
                $favourites->where('product_id', $productId)->whereNull('variant_id')->delete();
            } else {
                $favouritesModel->create($data);
            }
        }

        return $this->index();
    }
}