<?php

namespace AdminEshop\Models\Clients;

use AdminEshop\Contracts\Discounts\ClientsProducts;
use AdminEshop\Models\Clients\Client;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Discounts;

class ClientsProductsDiscount extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2020-09-15 20:04:51';

    /*
     * Template name
     */
    protected $name = 'Zľavy na konkretné produkty';

    /*
     * Template title
     */
    protected $title = 'Nastavte zľavu na produkt, pre konkretneho klienta.';

    /*
     * Model Parent
     * Eg. Article::class
     */
    protected $belongsToModel = Client::class;

    protected $sortable = false;

    protected $icon = 'fa-percent';

    protected $settings = [
        'increments' => false,
        'title.update' => 'Upravujete klientsku zľavu č. :id',
    ];

    public function active()
    {
        return Discounts::isRegistredDiscount(ClientsProducts::class);
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
            'Produkt' => Group::fields([
                'product' => 'name:Produkt|belongsTo:products,name',
                'variant' => 'name:Varianta|belongsTo:products_variants,name',
            ]),
            'Zľava' => Group::fields([
                'discount_operator' => 'name:Typ zľavy|type:select|required_with:discount|hidden',
                'discount' => 'name:Výška zľavy|type:decimal|hideFieldIfIn:discount_operator,NULL,default|required_if:discount_operator,'.implode(',', array_keys(operator_types())).'|hidden',
            ])->id('discount'),
        ];
    }

    public function options()
    {
        return [
            'discount_operator' => [ 'default' => 'Žiadna zľava' ] + operator_types(),
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
}