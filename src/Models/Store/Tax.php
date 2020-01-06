<?php

namespace AdminEshop\Models\Store;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class Tax extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 17:52:15';

    /*
     * Template name
     */
    protected $name = 'Sadzby DPH';

    protected $group = 'store.settings';

    protected $sortable = false;

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
            'name' => 'name:Názov|required',
            'tax' => 'name:Sazba DPH|type:decimal|required',
        ];
    }

    protected $settings = [
        'title.insert' => 'Nová sadzba',
        'title.update' => ':name',
        'increments' => true,
    ];

}