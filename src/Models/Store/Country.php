<?php

namespace AdminEshop\Models\Store;

use AdminEshop\Admin\Rules\SetDefault;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class Country extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 17:51:25';

    /*
     * Template name
     */
    protected $name = 'Krajiny';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'store.settings';

    protected $reversed = true;

    protected $visible = ['id', 'name', 'code'];

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
            'name' => 'name:Krajina|required',
            'code' => 'name:Skratka krajiny|max:5|required',
            'default' => 'name:Predvolená krajina|type:checkbox',
        ];
    }

    protected $settings = [
        'title.insert' => 'Nová krajina',
        'title.update' => ':name',
        'columns.id.hidden' => true,
    ];

    protected $rules = [
        SetDefault::class,
    ];
}