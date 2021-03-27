<?php

namespace AdminEshop\Models\Orders;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class OrdersLog extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2020-03-27 06:49:15';

    /*
     * Template name
     */
    protected $name = 'Hlásenia';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $icon = 'fa-exclamation-triangle';

    /*
     * Model Parent
     * Eg. Articles::class,
     */
    protected $belongsToModel = Order::class;

    protected $publishable = false;

    protected $sortable = false;

    protected $insertable = false;

    protected $active = false;

    public $timestamps = false;

    protected $options = [
        'type' => [
            'info' => 'Informácia',
            'error' => 'Chyba',
            'success' => 'Úspech',
        ],
        'code' => [
            'email-client-error' => 'Neúspešne odoslaný email zázkazníkovy',
            'email-store-error' => 'Neúspešne odoslaný email obchodu',
            'email-payment-done-error' => 'Neúspešne odoslaný email zázkazníkovy pri potvrdení platby',
            'payment-canceled' => 'Platba bola zrušená zákazníkom, alebo neprebehla v poriadku.',
            'payment-error' => 'Platbu nebolo možné zrealizovať.',
        ],
    ];

    protected $settings = [
        'title.insert' => 'Nové hlásenie',
    ];

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'type' => 'name:Typ hlásenia|type:select|default:info|required',
            'code' => 'name:Kód hlásenia|type:select',
            'message' => 'name:Doplnková správa',
            'log' => 'name:Log|type:text',
            'created_at' => 'name:Vytvorené|type:datetime|default:CURRENT_TIMESTAMP',
        ];
    }
}