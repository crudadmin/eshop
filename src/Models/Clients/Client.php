<?php

namespace AdminEshop\Models\Clients;

use AdminEshop\Contracts\Discounts\ClientPercentage;
use AdminEshop\Eloquent\Concerns\HasUsernames;
use Admin\Eloquent\Authenticatable;
use Admin\Fields\Group;
use Discounts;
use Illuminate\Notifications\Notifiable;

class Client extends Authenticatable
{
    use HasUsernames;

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
            'Osobné údaje' => Group::fields(array_merge(
                [
                    Group::fields([
                        'email' => 'name:Email|email|required|unique:clients,email,'.(isset($row) ? $row->getKey() : 'NULL').',id,deleted_at,NULL',
                        'photo' => 'name:Fotografia|type:file|image',
                    ])->inline(),
                    'username' => 'name:Meno a priezvisko'.(config('admineshop.client.username_splitted') ? '|removeFromForm' : ''),
                    Group::inline([
                        'firstname' => 'name:Meno',
                        'lastname' => 'name:Priezvisko',
                    ])->add('hidden'.(!config('admineshop.client.username_splitted') ? '|removeFromForm' : ''))->attributes(!config('admineshop.client.username_splitted') ? 'hideFromForm' : ''),
                    'phone' => 'name:Telefon|'.phoneValidatorRule(),
                    'password' => 'name:Heslo|type:password|min:6|confirmed|max:40'.( ! isset($row) ? '|required' : '' ),
                ],
                config('admineshop.client.groups', false)
                    ? ['groups' => 'name:Skupina klienta|belongsToMany:clients_groups,name|canAdd'] : []
            ))->id('personal'),
            'Fakturačné údaje' => Group::half([
                'street' => 'name:Ulica a č.p.',
                'city' => 'name:Mesto',
                'zipcode' => 'name:PSČ|zipcode',
                'country' => 'name:Krajina|belongsTo:countries,name|exists:countries,id',
            ])->id('billing'),
            'Firemné údaje' => Group::half([
                'company_name' => 'name:Názov firmy|required_with:is_company',
                'company_id' => 'name:IČO|company_id|required_with:is_company',
                'company_tax_id' => 'name:DIČ|required_with:is_company',
                'company_vat_id' => 'name:IČ DPH',
            ])->add('hidden'),
            'Zľavy' => Group::tab(array_merge(
                Discounts::isRegistredDiscount(ClientPercentage::class)
                    ? ['percentage_discount' => 'name:Zľava na všetky produkty|type:decimal|default:0'] : []
            ))->id('discounts')->icon('fa-percentage'),
        ];
    }

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

    public function setAdminRowsAttributes($attributes)
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

    public function setClientResponse()
    {
        return $this;
    }
}