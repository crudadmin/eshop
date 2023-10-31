<?php

namespace AdminEshop\Models\Attribute;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

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

    public function options()
    {
        return [
            'format' => [
                'string' => _('Textové pole'),
                'number' => _('Čiselná hodnota'),
                'decimal' => _('Čiselná hodnota s možnosťou desatinných miest'),
                'color' => _('Farba'),
            ],
        ];
    }

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
            'unit' => 'name:Merná jednotka|'.(Store::isEnabledLocalization() ? '|locale' : ''),
            'format' => 'name:Formát jednotky|type:select|default:string|required',
            Group::inline([
                'space' => 'name:Medzera pred mernou jednotkou|type:checkbox|default:0',
                'prepend' => 'name:Vložiť pred text|type:checkbox|inAdmin:default:0|'.(Store::isEnabledLocalization() ? '|locale' : ''),
            ]),
        ];
    }

    public function getIsNumericTypeAttribute()
    {
        return in_array($this->format, ['number', 'decimal']);
    }
}