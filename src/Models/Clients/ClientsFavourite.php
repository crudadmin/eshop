<?php

namespace AdminEshop\Models\Clients;

use AdminEshop\Models\Clients\Client;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class ClientsFavourite extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2020-01-22 15:04:51';

    /*
     * Template name
     */
    protected $name = 'Obľubene položky klienta';

    /*
     * Template title
     */
    protected $title = '';

    /*
     * Model Parent
     * Eg. Article::class
     */
    protected $belongsToModel = Client::class;

    protected $sortable = false;
    protected $publishable = false;
    protected $insertable = false;
    protected $editable = false;

    protected $settings = [
        'dates' => true,
        'increments' => false,
    ];

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

    public function scopeResponseQuery($query)
    {
        $query->with([
            'product',
            'variant'
        ]);
    }

    public function toResponseFormat()
    {
        return $this;
    }
}