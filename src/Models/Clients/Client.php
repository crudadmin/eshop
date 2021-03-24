<?php

namespace AdminEshop\Models\Clients;

use Admin\Eloquent\Authenticatable;
use Admin\Fields\Group;
use Illuminate\Notifications\Notifiable;

class Client extends Authenticatable
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-22 14:56:18';

    /*
     * Template name
     */
    protected $name = 'Zoznam zákazníkov';

    protected $group = 'clients';

    protected $publishable = true;

    protected $appends = ['is_company', 'thumbnail'];

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
                Group::fields([
                    'email' => 'name:Email|email|required|unique:clients,email,'.(isset($row) ? $row->getKey() : 'NULL').',id,deleted_at,NULL',
                    'photo' => 'name:Fotografia|type:file|image',
                ])->inline(),
                'password' => 'name:Heslo|type:password|min:4|confirmed|max:40'.( ! isset($row) ? '|required' : '' ),
                'username' => 'name:Meno a priezvisko',
                'phone' => 'name:Telefon|'.phoneValidatorRule(),
                'groups' => 'name:Skupina klienta|belongsToMany:clients_groups,name|canAdd',
            ]),
            'Fakturačné údaje' => Group::half([
                'street' => 'name:Ulica a č.p.',
                'city' => 'name:Mesto',
                'zipcode' => 'name:PSČ|zipcode',
                'country' => 'name:Krajina|belongsTo:countries,name|exists:countries,id',
            ]),
            'Firemné údaje' => Group::half([
                'company_name' => 'name:Názov firmy|required_with:is_company',
                'company_id' => 'name:IČO|company_id|required_with:is_company',
                'company_tax_id' => 'name:DIČ|required_with:is_company',
                'company_vat_id' => 'name:IČ DPH',
            ])->add('hidden'),
        ];
    }

    protected $settings = [
        'title.insert' => 'Nový klient',
        'title.update' => 'Klient :username',
        'grid' => [
            'default' => 'full',
            'enabled' => false,
        ],
        'columns.orders.name' => 'Počet obj.',
        'columns.orders.before' => 'last_logged_at',
        'columns.last_order.name' => 'Posledná objednávka',
    ];

    public function getClientNameAttribute()
    {
        if ( $this->company_name )
            return $this->company_name;

        return $this->username;
    }

    public function getIsCompanyAttribute()
    {
        return $this->company_name || $this->company_id || $this->company_tax_id || $this->company_vat_id;
    }

    public function setAdminAttributes($attributes)
    {
        $attributes['orders'] = $this->orders()->count();
        $attributes['last_order'] = ($order = $this->orders()->first()) ? $order->created_at->format('d.m.Y') : '';

        return $attributes;
    }

    public function getThumbnailAttribute()
    {
        if ( $this->photo ){
            return $this->photo->resize(300, 300)->url;
        }
    }
}