<?php

namespace AdminEshop\Models\Delivery;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Basket;

class Delivery extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 17:48:16';

    /*
     * Template name
     */
    protected $name = 'Doprava';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'store.settings';

    protected $reversed = true;

    protected $reserved = [2, 4, 5];

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
            'name' => 'name:Názov dopravy|placeholder:Zadejte názov dopravy|required|max:90',
            'tax' => 'name:Sazba DPH|belongsTo:taxes,:name (:tax%)|canAdd',
            'price' => 'name:Zakladná cena bez DPH|type:decimal|required',
            'description' => 'name:Popis dopravy|type:editor|hidden',
            Group::fields([
                'code' => 'name:Kód dopravy',
                'enabled' => 'name:Aktivovaná|type:checkbox|default:1|hidden',
            ])->inline(),

            'Omezení dopravy' => Group::tab([
                'payments' => 'name:Platobné metódy|belongsToMany:payments_methods,name|canAdd',
                'countries' => 'name:Krajiny|belongsToMany:countries,name|canAdd',
                'groups' => 'name:Uživatelské skupiny|belongsToMany:clients_groups,name|canAdd',
                'rules' => 'name:Prepravné náklady|belongsToMany:deliveries_rules,name|canAdd',
            ])->icon('fa-gear'),
        ];
    }

    protected $settings = [
        'title.insert' => 'Nová doprava',
        'title.update' => ':name',
        'columns.id.hidden' => true,
    ];

    public function getPriceWithoutTaxAttribute()
    {
        $price = $this->attributes['price'];

        return $price;
    }

    /*
     * Return price with tax
     */
    public function getPriceWithTaxAttribute()
    {
        return Basket::priceWithTax($this->priceWithoutTax, $this->tax_id);
    }
}