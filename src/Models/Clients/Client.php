<?php

namespace AdminEshop\Models\Clients;

use Gogol\Admin\Models\Authenticatable;
use Illuminate\Notifications\Notifiable;
use Gogol\Admin\Fields\Group;
use Carbon\Carbon;
use DB;

class Client extends Authenticatable
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-22 14:56:18';

    /*
     * Template name
     */
    protected $name = 'Seznam klientů';

    protected $group = 'clients';

    protected $publishable = true;

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
                'email' => 'name:Email|email|required|unique:clients,email,'.(isset($row) ? $row->getKey() : 'NULL').',id,deleted_at,NULL',
                'password' => 'name:Heslo|type:password|min:4|confirmed|max:40'.( ! isset($row) ? '|required' : '|nullable' ),
                'firstname' => 'name:Meno',
                'lastname' => 'name:Priezvisko',
                'phone' => 'name:Telefon|phone:CZ,SK',
                'groups' => 'name:Skupina klienta|belongsToMany:clients_groups,name|canAdd',
            ])->grid(4),
            'Fakturační údaje' => Group::fields([
                'street' => 'name:Ulice a č.p.',
                'city' => 'name:Mesto',
                'zipcode' => 'name:PSČ',
                'country' => 'name:Krajina|belongsTo:countries,name|exists:countries,id',
            ])->grid(4),
            'Firemní údaje' => Group::fields([
                'company_name' => 'name:Názov firmy|required_with:is_company',
                'company_id' => 'name:IČO|required_with:is_company|numeric|max:99999999',
                'company_tax_id' => 'name:DIČ|required_with:is_company|dic',
                'company_vat_id' => 'name:IČ DPH',
            ])->grid(4)->add('hidden'),
            'Ostatné údaje' => Group::fields([
                'last_logged_at' => 'name:Posledné prihlásenie|type:datetime',
            ])->grid(4),
        ];
    }

    protected $settings = [
        'title.insert' => 'Nový klient',
        'title.update' => 'Klient :firstname :lastname',
        'grid' => [
            'default' => 'full',
            'enabled' => false,
        ],
        'columns.orders.name' => 'Objednávka',
        'columns.orders.before' => 'last_logged_at',
        'columns.last_order.name' => 'Posledná objednávka',
    ];

    public function getUsernameAttribute()
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function getClientNameAttribute()
    {
        if ( $this->company_name )
            return $this->company_name;

        return $this->username;
    }

    public function isCompany()
    {
        return $this->company_name || $this->company_id || $this->company_tax_id;
    }

    public function setAdminAttributes($attributes)
    {
        $attributes['orders'] = $this->orders->count();
        $attributes['last_order'] = ($order = $this->orders->first()) ? $order->created_at->format('d.m.Y') : '';

        return $attributes;
    }
}