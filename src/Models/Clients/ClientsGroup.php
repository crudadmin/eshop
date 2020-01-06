<?php

namespace AdminEshop\Models\Clients;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class ClientsGroup extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-22 14:57:18';

    /*
     * Template name
     */
    protected $name = 'Skupiny klientov';

    protected $group = 'clients';

    protected $publishable = false;

    protected $icon = 'fa-group';

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
            'name' => 'name:Názov skupiny|required',
            'b2b' => 'name:B2B skupina|title:Zobraziť ceny bez DPH|type:checkbox|default:0',
        ];
    }

    protected $settings = [
        'title.insert' => 'Nová skupina',
        'title.update' => 'Skupina :name',
        'columns.id.hidden' => true,
    ];
}