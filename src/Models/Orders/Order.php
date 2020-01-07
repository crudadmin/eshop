<?php

namespace AdminEshop\Models\Orders;

use Admin\Eloquent\AdminModel;
use Illuminate\Notifications\Notifiable;
use Admin\Fields\Group;
use AdminEshop\Traits\OrderTrait;
use Store;

class Order extends AdminModel
{
    use Notifiable,
        OrderTrait;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 05:49:15';

    /*
     * Template name
     */
    protected $name = 'Objednávky';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'store';

    protected $publishable = false;
    protected $sortable = false;
    protected $insertable = false;
    protected $history = true;

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
            'client' => 'name:Klient|belongsTo:clients|invisible',
            'Fakturační údaje' => Group::fields([
                'email' => 'name:Kontaktný email|email|required',
                'phone' => 'name:Telefón|required|phone:CZ,SK',
                'firstname' => 'name:Krstné meno|required|hidden',
                'lastname' => 'name:Priezvisko|required|hidden',
                'street' => 'name:Ulice a č.p.|required',
                'city' => 'name:Mesto|required',
                'zipcode' => 'name:PSČ|required',
                'country' => 'name:Krajiny|hidden|belongsTo:countries,name|exists:countries,id',
            ])->grid(4),
            'Dodací údaje' => Group::fields([
                'delivery_different' => 'name:Doručiť na inú adresu|type:checkbox|default:0',
                'delivery_phone' => 'name:Telefón|required_with:different_delivery',
                'delivery_company_name' => 'name:Názov firmy',
                'delivery_firstname' => 'name:Krstné meno|required_with:different_delivery',
                'delivery_lastname' => 'name:Priezvisko|required_with:different_delivery',
                'delivery_street' => 'name:Ulice a č.p.|required_with:different_delivery',
                'delivery_city' => 'name:Mesto|required_with:different_delivery',
                'delivery_zipcode' => 'name:PSČ|required_with:different_delivery',
                'delivery_country' => 'name:Krajina|belongsTo:countries,name|exists:countries,id|required_with:different_delivery',
            ])->add('hidden')->grid(4),
            Group::fields([
                'Firemní údaje' => Group::fields([
                    'company_name' => 'name:Názov firmy|required_with:is_company|hidden',
                    'company_id' => 'name:IČ|required_with:is_company|numeric|hidden',
                    'company_tax_id' => 'name:DIČ|required_with:is_company|hidden',
                    'company_vat_id' => 'name:IČ DPH|hidden',
                ]),
                'Nastavenia objednávky' => Group::fields([
                    'note' => 'name:Poznámka|type:text|hidden',
                    'internal_note' => 'name:Interná poznámka|type:text|hidden',
                    'pdf' => 'name:PDF|type:file|extensions:pdf|invisible',
                    'status' => 'name:Stav objednávky|type:select|required|default:new',
                ]),
            ])->grid(4),
            'Platba a ceny' => Group::fields([
                'Doprava' => Group::fields([
                    'delivery' => 'name:Typ dopravy|belongsTo:deliveries,name',
                    'delivery_tax' => 'name:DPH dopravy|hidden|type:decimal',
                    'delivery_price' => 'name:Cena za dopravu|type:decimal|component:priceField|hidden',
                ])->grid(4)->add('required'),
                'Platební metoda' => Group::fields([
                    'payment_method' => 'name:Platobná metóda|belongsTo:payments_methods,name',
                    'payment_method_tax' => 'name:DPH plat. metody (%)|hidden|type:decimal',
                    'payment_method_price' => 'name:Cena plat. metódy bez DPH|type:decimal|component:priceField|hidden',
                ])->grid(4)->add('required'),
                'Objednávka' => Group::fields([
                    Group::fields([
                        'price' => 'name:Cena bez DPH|disabled|type:decimal',
                        'price_tax' => 'name:Cena s DPH|disabled|type:decimal',
                    ]),
                ])->grid(4),
            ]),
        ];
    }

    public function settings()
    {
        return [
            'title.insert' => 'Nová objednávka',
            'title.update' => 'Objednávka č. :id - :created',
            'grid.enabled' => false,
            'grid.default' => 'full',
            'columns.price.add_after' => ' '.Store::getCurrency(),
            'columns.price_tax.add_after' => ' '.Store::getCurrency(),
            'columns.created.name' => 'Vytvorená dňa',
            'columns.client_name' => [
                'after' => 'id',
                'name' => 'Zákazník',
            ],
            'columns.username' => [
                'after' => 'id',
                'name' => 'Meno a priezvisko',
            ],
        ];
    }

    protected $options = [
        'status' => [
            'new' => 'Prijatá',
            'waiting' => 'čaka za spracovaním',
            'delivery' => 'Doručuje sa',
            'payment-waiting' => 'Čaká na zaplatenie',
            'paid' => 'Zaplatená',
            'ok' => 'Vybavená',
            'canceled' => 'Zrušená',
        ],
    ];

    public function setAdminAttributes($attributes)
    {
        $attributes['created'] = $this->created_at ? $this->created_at->format('d.m.Y H:i') : '';

        $attributes['client_name'] = $this->client ? $this->client->clientName : '';

        $attributes['username'] = $this->username;

        return $attributes;
    }

    public function isCompany()
    {
        return $this->company_name || $this->company_id || $this->company_tax_id;
    }

    public function getNumberAttribute()
    {
        return str_pad($this->getKey(), 6, '0', STR_PAD_LEFT);
    }


    public function getUsernameAttribute()
    {
        if ( !$this->firstname && !$this->lastname )
            return null;

        return $this->firstname . ' ' . $this->lastname;
    }

    public function getDeliveryUsernameAttribute()
    {
        if ( count(array_filter([$this->delivery_firstname, $this->delivery_lastname])) == 0 )
            return null;

        return $this->delivery_firstname . ' ' . $this->delivery_lastname;
    }

    public function getPaymentMethodPriceWithTaxAttribute()
    {
        return $this->payment_method_price * (1 + ($this->payment_method_tax/100));
    }

    public function getDeliveryPriceWithTaxAttribute()
    {
        return $this->delivery_price * (1 + ($this->delivery_tax/100));
    }
}