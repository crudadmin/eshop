<?php

namespace AdminEshop\Models\Delivery;

use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Fields\Group;
use AdminEshop\Models\Products\ProductsCategory;

class DeliveriesRule extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 17:49:26';

    /*
     * Template name
     */
    protected $name = 'Prepravné náklady';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'store.settings.general';

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
            'name' => 'name:Názov|placeholder:Zadajte názov prepravných nákladov|required|max:90',
            Group::half([
                'price_operator' => 'name:Spôsob upravy ceny|type:select|hidden|default:abs|required_with:price',
            ]),
            Group::half([
                'price' => 'name:Hodnota úpravy ceny|type:decimal|hidden|required_with:price_operator',
            ]),

            'Omezení dopravy' => Group::tab([
                'payments' => 'name:Platebné metody|belongsToMany:payments_methods,name|canAdd',
                'countries' => 'name:Krajiny|belongsToMany:countries,name|canAdd',
                'groups' => 'name:Uživatelské skupiny|belongsToMany:clients_groups,name|canAdd',
            ])->add('hidden')->icon('fa-gear'),

            'Podmínky' => Group::tab([
                Group::half([
                    'basket_from' => 'name:Minimálna cena košíku|type:decimal|default:0',
                ]),
                Group::half([
                    'basket_to' => 'name:Maximálna cena košíku|type:decimal|default:0',
                ]),
                'tax' => 'name:Aplikovat podmienku na cenu s DPH|type:checkbox|default:1',
                'categories' => 'name:Platí okrem produktov v kategórii',
                'type' => 'name:Typ podmienky|type:select|required'
            ])->add('hidden')->icon('fa-gear'),
        ];
    }

    protected $settings = [
        'title.insert' => 'Novy prepravný náklad',
        'title.update' => ':name',
        'columns.id.hidden' => true,
    ];

    protected $rules = [
        \AdminEshop\Rules\CanSetWithoutDPH::class,
    ];

    public function options()
    {
        return [
            'price_operator' => operator_types(),
            'type' => [
                'all' => 'Vždy, len ovlivňuje cenu',
                'valid' => 'Košík musí splniať podmienky',
                'invalid' => 'Košík nesmie splniať podmienky',
            ],
        ];
    }
}