<?php

namespace AdminEshop\Models\Clients;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class ClientsDelivery extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-22 15:52:18';

    /*
     * Template name
     */
    protected $name = 'Dodacie/Fakturačné adresy';

    protected $publishable = false;
    protected $sortable = false;

    protected $belongsToModel = Client::class;

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    public function fields($row)
    {
        return [
            'Osobné údaje' => Group::fields([
                'type' => 'name:Typ adresy|type:select|required',
                'name' => 'name:Názov adresy|required',
                'firstname' => 'name:Krstné meno|required',
                'lastname' => 'name:Priezvisko|required',
                'phone' => 'name:Tel. číslo|required|phone:CZ,SK',
                'default' => 'name:Predvolené|default:0|type:checkbox',
            ]),

            'Adresa' => Group::half([
                'street' => 'name:Ulice a č.p.|required',
                'city' => 'name:Mesto|required',
                'zipcode' => 'name:PSČ|required',
                'country' => 'name:Krajina|belongsTo:countries,name|required|exists:countries,id',
            ])->grid(4),

            'Firemné údaje' => Group::fields([
                'company_name' => 'name:Názov firmy|component:companyField',
                'company_id' => 'name:IČO|numeric',
                'tax_id' => 'name:DIČ|dic',
                'vat_id' => 'name:IČ DPH|dic',
            ])->grid(4)->add('hidden'),
        ];
    }

    protected $settings = [
        'title.insert' => 'Nová adresa',
        'grid' => [
            'default' => 'full',
            'enabled' => false,
        ],
        'columns.default.title' => 'Predvolená'
    ];

    protected $options = [
        'type' => [
            'delivery' => 'Dodacia adresa',
            'billing' => 'Fakturačná adresa',
        ],
    ];

    public function isCompany()
    {
        if ( $this->type == 'delivery' )
            return false;

        return $this->company_name || $this->company_id || $this->company_tax_id;
    }

    public function getUsernameAttribute()
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    protected $rules = [
        \AdminEshop\Rules\SetDefaultDeliveryAddress::class,
    ];
}