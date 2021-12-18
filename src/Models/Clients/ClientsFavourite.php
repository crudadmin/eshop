<?php

namespace AdminEshop\Models\Clients;

use Admin;
use AdminEshop\Models\Clients\Client;
use AdminEshop\Models\Store\CartToken;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Cart;

class ClientsFavourite extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2020-01-22 15:04:51';

    /*
     * Template name
     */
    protected $name = 'Obľubene položky';

    /*
     * Template title
     */
    protected $title = '';

    protected $sortable = false;
    protected $publishable = false;
    protected $insertable = false;
    protected $editable = false;

    protected $settings = [
        'dates' => true,
        'increments' => false,
    ];

    public function belongsToModel()
    {
        return [
            Client::class,
            CartToken::class,
        ];
    }

    public function active()
    {
        return config('admineshop.client.favourites', false);
    }

    /*
     * Automatic form and database generator by fields list
     * :name - field name
     * :type - field type (string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio)
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'product' => 'name:Produkt|belongsTo:products,name',
            'variant' => 'name:Varianta|belongsTo:products_variants,name',
        ];
    }

    public function mutateFields($fields)
    {
        //If variants are not defined in eshop
        if ( !config('admineshop.product_types.variants') ){
            $fields->field('variant_id', function($field){
                $field->invisible = true;
            });
        }
    }

    public function scopeWithFavouriteResponse($query)
    {
        $query->with([
            'product' => function($query){},
            'variant' => function($query){}
        ])->whereHas('product');
    }

    public function setFavouriteResponse()
    {
        $this->product?->setCategoryResponse();
        $this->variant?->setCategoryResponse();

        return $this;
    }

    public function getFavouritesIdentifiers($client = null)
    {
        $identifiers = [];

        if ( $client = ($client ?: client()) ){
            $identifiers['client_id'] = $client->getKey();
        }

        if ( ($cartToken = Cart::getDriver()->getCartSession()) instanceof CartToken ){
            $identifiers['cart_token_id'] = $cartToken->getKey();
        }

        return $identifiers;
    }

    public function scopeActiveSession($query)
    {
        $identifiers = $this->getFavouritesIdentifiers();

        //We cannot select all when identifiers are not present.
        if ( count($identifiers) == 0 ){
            $query->where('id', 0);
        }

        $query->where(function($query) use ($identifiers) {
            $i = 0;
            foreach ($identifiers as $key => $value) {
                $query->{ $i == 0 ? 'where' : 'orWhere' }($key, $value);
                $i++;
            }
        });
    }
}