<?php

namespace AdminEshop\Models\Store;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class AttributesUnit extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2020-04-01 10:28:15';

    /*
     * Template name
     */
    protected $name = 'Merné jednotky';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $publishable = false;

    protected $reversed = true;

    protected $inMenu = false;

    public function settings()
    {
        return [
            'title.insert' => 'Nová merná jednotka',
            'title.update' => ':name',
            'buttons.create' => 'Pridať mernú jednotku',
            'columns.id.hidden' => env('APP_DEBUG') == false,
        ];
    }

    protected $options = [
        'format' => [
            'string' => 'Textové pole',
            'number' => 'Čiselná hodnota',
            'decimal' => 'Čiselná hodnota s možnosťou desatinných miest',
        ],
    ];

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
            'name' => 'name:Názov mernej jednotky|required',
            'unit' => 'name:Merná jednotka|required',
            'format' => 'name:Formát jednotky|type:select|default:string|required',
        ];
    }

    public function getIsNumericTypeAttribute()
    {
        return in_array($this->format, ['number', 'decimal']);
    }
}