<?php

namespace AdminEshop\Models\Store;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class Store extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-03 17:40:15';

    /*
     * Template name
     */
    protected $name = 'Obchod';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'store.settings.general';

    protected $single = true;

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
            'email' => 'name:Email obchodu|email',
            'rounding' => 'name:Zaokrúhľovanie čísel|type:select|default:0|required',
        ];
    }

    protected $options = [
        'rounding' => [
            2 => 'na 2 desetinné miesta',
            1 => 'na 1 desetinné miesto',
            0 => 'na celé čísla',
        ]
    ];

}