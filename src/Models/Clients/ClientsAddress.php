<?php

namespace AdminEshop\Models\Clients;

use AdminEshop\Admin\Rules\SetDefaultDeliveryAddress;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class ClientsAddress extends AdminModel
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

    protected $hidden = ['created_at', 'deleted_at', 'published_at', '_order'];

    protected $settings = [
        'title.insert' => 'Nová adresa',
        'grid' => [
            'default' => 'full',
            'enabled' => false,
        ],
        'columns.default.title' => 'Predvolená'
    ];

    protected $rules = [
        SetDefaultDeliveryAddress::class,
    ];

    protected $options = [
        'type' => [
            'delivery' => 'Dodacia adresa',
            'billing' => 'Fakturačná adresa',
        ],
    ];

    public function belongsToModel()
    {
        return Client::class;
    }

    public function active()
    {
        return config('admineshop.client.addresses', false);
    }

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
            'type' => 'name:Typ adresy|type:select|required',
            'name' => 'name:Názov adresy|required',
            'Osobné údaje' => Group::fields([
                'username' => 'name:Meno a priezvisko / Firma|required',
                'phone' => 'name:Tel. číslo|required|'.phoneValidatorRule(),
                'default' => 'name:Predvolené|default:0|type:checkbox',
            ]),

            'Adresa' => Group::half([
                'street' => 'name:Ulica a č.p.|required',
                'city' => 'name:Mesto|required',
                'zipcode' => 'name:PSČ|required|zipcode',
                'country' => 'name:Krajina|belongsTo:countries,name|required|exists:countries,id',
            ])->grid(6),

            'Firemné údaje' => Group::fields([
                'company_name' => 'name:Názov firmy|required_with:is_company',
                'company_id' => 'name:IČO|company_id|required_with:is_company',
                'company_tax_id' => 'name:DIČ|required_with:is_company',
                'company_vat_id' => 'name:IČ DPH',
            ])->grid(6)->add('hidden|removeFromFormIf:type,delivery'),
        ];
    }

    public function isCompany()
    {
        if ( $this->type == 'delivery' ) {
            return false;
        }

        return $this->company_name || $this->company_id || $this->company_tax_id || $this->company_vat_id;
    }

    public function getAddressValidator()
    {
        return $this->validator()->only([
            'type', 'name',
            'username', 'phone',
            'street', 'zipcode', 'city', 'country_id',
        ]);
    }
}