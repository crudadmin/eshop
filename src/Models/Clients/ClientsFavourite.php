<?php

namespace AdminEshop\Models\Clients;

use AdminEshop\Models\Clients\Client;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Admin;

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
        return get_class(Admin::getModel('Client'));
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
        ];
    }

    public function scopeWithFavouriteResponse($query)
    {
        $query->select('id', 'client_id', 'product_id')->with([
            'product' => function($query){
                $query->withFavouriteResponse();
            },
        ])->whereHas('product');
    }

    public function setFavouriteResponse()
    {
        if ( $this->product ) {
            $this->product->setFavouriteResponse();
        }

        return $this;
    }
}