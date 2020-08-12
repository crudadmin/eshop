<?php

namespace AdminEshop\Models\Store;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class CartSession extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2020-08-03 16:48:15';

    /*
     * Template name
     */
    protected $name = 'Košík';

    protected $active = false;

    protected $sortable = false;
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
            'key' => 'name:Customer key|max:255|index',
            'client_id' => 'name:Client id|belongsTo:clients,id',
            'data' => 'name:Data|type:json',
        ];
    }
}