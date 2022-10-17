<?php

namespace AdminEshop\Models\Store;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Gogol\Invoices\Admin\Rules\SetDefault;

class Currency extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2021-10-17 13:51:25';

    /*
     * Template name
     */
    protected $name = 'Meny';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'store';

    protected $reversed = true;

    protected $publishable = false;

    protected $sortable = false;

    protected $icon = 'fa-coins';

    protected $settings = [
        'title.insert' => 'Nová mena',
        'title.update' => ':name',
        'columns.id.hidden' => true,
    ];

    protected $rules = [
        SetDefault::class,
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
            'name' => 'name:Názov meny|required',
            'code' => 'name:Kód meny|placeholder:EUR,USD|required',
            'char' => 'name:Značka meny|required|max:6|placeholder:€, $, EUR, USD...',
            'rate' => 'name:Kurz|type:decimal|title:Kurz voči predvolenej mene|required',
            'default' => 'name:Predvolená mena|type:checkbox|default:0',
        ];
    }

    public function onTableCreate()
    {
        //When roles table is created, set all users as super admins.
        $this->create([
            'name' => 'Euro',
            'code' => 'eur',
            'char' => '€',
            'rate' => 1,
            'default' => 1,
        ]);
    }

    public function setResponse()
    {
        return $this->setVisible(['id', 'name', 'char', 'code']);
    }
}