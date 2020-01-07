<?php

namespace AdminEshop\Models\Store;

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
            'code' => 'name:Skratka krajiny|max:5|required'
        ];
    }

    protected $settings = [
        'title.insert' => 'Nová krajina',
        'title.update' => ':name',
        'columns.id.hidden' => true,
    ];
}