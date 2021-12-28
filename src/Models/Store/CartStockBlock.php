<?php

namespace AdminEshop\Models\Store;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class CartStockBlock extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2020-10-13 07:53:15';

    /*
     * Template name
     */
    protected $name = 'Blokácia košíka';

    protected $active = false;

    protected $sortable = false;
    protected $publishable = false;
    public $timestamps = false;

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'cart_token' => 'name:Customer token|belongsTo:cart_tokens',
            'product_id' => 'name:Produkt|belongsTo:products,id',
            'variant_id' => 'name:Produkt|belongsTo:products_variants,id',
            'quantity' => 'name:Blokovaná kvantita|type:integer|min:0',
            'blocked_at' => 'name:Posledná blokácia|type:datetime|required',
        ];
    }
}