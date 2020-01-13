<?php

namespace AdminEshop\Models\Store;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class PaymentsMethod extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 17:48:18';

    /*
     * Template name
     */
    protected $name = 'Platobné metódy';

    protected $group = 'store.settings';

    protected $publishable = false;

    protected $icon = 'fa-money';

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
            'name' => 'name:Názov platby|max:40|required',
            'description' => 'name:Popis platby|type:text',
            'tax' => 'name:Sazba DPH|belongsTo:taxes,:name (:tax%)|canAdd',
            'price' => 'name:Základna cena bez DPH|type:decimal|required',
        ];
    }

    protected $hidden = ['created_at', 'deleted_at', 'updated_at', 'description'];

    protected $settings = [
        'title.update' => ':name',
        'columns.id.hidden' => true,
    ];
}