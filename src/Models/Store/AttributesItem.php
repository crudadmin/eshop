<?php

namespace AdminEshop\Models\Store;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class AttributesItem extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-11 17:48:15';

    /*
     * Template name
     */
    protected $name = 'Hodnoty atribútu';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $belongsToModel = Attribute::class;

    protected $inTab = true;
    protected $withoutParent = true;
    protected $publishable = false;
    protected $reversed = true;
    protected $sluggable = 'name';

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
            'name' => 'name:Hodnota atribútu|required',
        ];
    }

    protected $settings = [
        'title.insert' => 'Nová hodnota atribútu',
        'title.update' => ':name',
        'columns.id.hidden' => true,
    ];

}