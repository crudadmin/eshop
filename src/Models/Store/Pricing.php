<?php

namespace AdminEshop\Models\Store;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use AdminEshop\Models\Products\Product;

class Pricing extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 17:54:16';

    /*
     * Template name
     */
    protected $name = 'Cenníky';

    protected $group = 'store.products';

    protected $publishable = false;

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
            'name' => 'name:Názov cenníku|required',
            'groups' => 'name:Priradenie klientských skupin|belongsToMany:clients_groups,name|canAdd',
            Group::half([
                'default' => 'name:Predvolený|type:checkbox',
            ]),
            Group::half([
                'b2b' => 'name:B2B Ceník|title:Zobrazi ceny bez DPH|type:checkbox|default:0',
            ]),
            'Sleva' => Group::fields([
                Group::half([
                    'price_operator' => 'name:Možnosti ceny|type:select|required_with:price',
                    'global' => 'name:Platnost zľavy|type:select|hidden|default:global|required',
                ]),
                Group::half([
                    'price' => 'name:Hodnota ceny|type:decimal|title:Odčítava sa od ceny bez DPH|required_with:price_operator',
                ]),
            ])
        ];
    }

    protected $settings = [
        'title.insert' => 'Nový cenník',
        'title.update' => ':name',
        'columns.id.hidden' => true,
        'refresh_interval' => 3000,
    ];

    protected $rules = [
        \AdminEshop\Rules\SetDefaultPricing::class,
    ];

    public function options()
    {
        return [
            'price_operator' => operator_types(),
            'global' => [
                'global' => _('Platí automatický pre všetky produkty'),
                'product' => _('Platí pre vybrané produkty, ktoré obsahuju daný cenník'),
            ],
        ];
    }
}