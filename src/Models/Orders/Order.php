<?php

namespace AdminEshop\Models\Orders;

use AdminEshop\Admin\Buttons\GenerateInvoice;
use AdminEshop\Admin\Rules\RebuildOrder;
use AdminEshop\Eloquent\Concerns\OrderTrait;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Store\Country;
use AdminEshop\Models\Store\PaymentsMethod;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Illuminate\Notifications\Notifiable;
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
            'Fakturačné údaje' => Group::fields([
                'username' => 'name:Meno a priezvisko|required|hidden',
                'email' => 'name:Email|email|required',
                'phone' => 'name:Telefón',
                'street' => 'name:Ulica a č.p.|column_name:Ulica|required',
                'city' => 'name:Mesto|required',
                'zipcode' => 'name:PSČ|required',
                'country' => 'name:Krajina|hidden|belongsTo:countries,name|defaultByOption:default,1|exists:countries,id',
            ])->grid(4),
            'Dodacie údaje' => Group::fields([
                'delivery_different' => 'name:Doručiť na inú adresu|type:checkbox|default:0',
                Group::fields([
                    'delivery_username' => 'name:Meno a priezvisko / Firma|required_with:delivery_different',
                    'delivery_phone' => 'name:Telefón|required_with:delivery_different',
                    'delivery_street' => 'name:Ulica a č.p.|required_with:delivery_different',
                    'delivery_city' => 'name:Mesto|required_with:delivery_different',
                    'delivery_zipcode' => 'name:PSČ|required_with:delivery_different',
                    'delivery_country' => 'name:Krajina|belongsTo:countries,name|exists:countries,id|defaultByOption:default,1|required_with:delivery_different',
                ])->add('hideFieldIfNot:delivery_different,1')
            ])->add('hidden')->grid(4),
            Group::fields([
                'Firemné údaje' => Group::fields([
                    'is_company' => 'name:Nákup na firmu|type:checkbox|default:0',
                    Group::fields([
                        'company_name' => 'name:Názov firmy|required_with:is_company',
                        'company_id' => 'name:IČ|required_with:is_company|numeric',
                        'company_tax_id' => 'name:DIČ|required_with:is_company',
                        'company_vat_id' => 'name:IČ DPH',
                    ])->add('hideFieldIfNot:is_company,1')
                ])->add('hidden'),
            ])->grid(4),
            'Nastavenia objednávky' => Group::fields([
                Group::fields([
                    'note' => 'name:Poznámka|type:text|hidden',
                    'internal_note' => 'name:Interná poznámka|type:text|hidden',
                ])->inline()
            ]),
            'Doprava' => Group::fields([
                Group::fields([
                    'delivery' => 'name:Doprava|belongsTo:deliveries,name|required',
                    'delivery_tax' => 'name:DPH dopravy %|readonlyIf:delivery_manual,0|fillBy:delivery.tax|required|hidden|type:select|default:'.Store::getDefaultTax(),
                    'delivery_manual' => 'name:Manuálna cena|hidden|type:checkbox|default:0|tooltip:Ak je manuálna cena zapnutá, nebude na cenu dopravy pôsobiť žiadna automatická zľava.',
                ])->inline(),
                'delivery_price' => 'name:Cena za dopravu|readonlyIf:delivery_manual,0|required|fillBy:delivery.price|type:decimal|component:PriceField|hidden',
            ])->grid(6),
            'Platobná metóda' => Group::fields([
                Group::fields([
                    'payment_method' => 'name:Platobná metóda|column_name:Platba|required|belongsTo:payments_methods,name',
                    'payment_method_tax' => 'name:DPH plat. metody %|readonlyIf:delivery_manual,0|fillBy:payment_method.tax|hidden|required|type:select|default:'.Store::getDefaultTax(),
                    'payment_method_manual' => 'name:Manuálna cena|hidden|type:checkbox|default:0|tooltip:Ak je manuálna cena zapnutá, nebude na poplatok za platobnú metódu pôsobiť žiadna automatická zľava.',
                ])->inline(),
                'payment_method_price' => 'name:Cena plat. metódy|readonlyIf:payment_method_manual,0|type:decimal|required|fillBy:payment_method.price|component:PriceField|hidden',
            ])->grid(6),
            Group::fields([
                'Cena objednávky' => Group::fields([
                    Group::fields([
                        'status' => 'name:Stav objednávky|column_name:Stav|type:select|required|default:new',
                        'paid_at' => 'name:Zaplatené dňa|type:datetime',
                    ])->inline(),
                    Group::fields([
                        'price' => 'name:Cena bez DPH|disabled|type:decimal',
                        'price_tax' => 'name:Cena s DPH|disabled|type:decimal',
                    ])->inline(),
                ])->width(6),
                'Zľavy' => Group::fields([
                    'discount_code' => 'name:Zľavový kód|belongsTo:discounts_codes,code|canAdd',
                ])->width(6),
            ])
        ];
    }

    public function settings()
    {
        return [
            'autoreset' => false,
            'title.insert' => 'Nová objednávka',
            'buttons.insert' => 'Vytvoriť novú objednávku',
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
        ];
    }

    protected $buttons = [
        GenerateInvoice::class,
    ];

    protected $rules = [
        RebuildOrder::class,
    ];

    public function options()
    {
        $countries = Country::all();

        $taxOptions = Store::getTaxes()->map(function($item){
            $item->taxValue = $item->tax.'%';
            return $item;
        })->pluck('taxValue', 'tax');

        return [
            'delivery_tax' => $taxOptions,
            'payment_method_tax' => $taxOptions,
            'country_id' => $countries,
            'delivery_country_id' => $countries,
            'delivery_id' => $this->getDeliveries(),
            'payment_method_id' => $this->getPaymentMethods(),
            'status' => [
                'new' => 'Prijatá',
                'waiting' => 'Čaká za spracovaním',
                'shipped' => 'Doručuje sa',
                'ok' => 'Vybavená',
                'canceled' => 'Zrušená',
            ],
        ];
    }

    public function getDeliveries()
    {
        return Delivery::leftJoin('taxes', 'deliveries.tax_id', '=', 'taxes.id')
                        ->select(['deliveries.id', 'deliveries.name', 'deliveries.price', 'taxes.tax'])
                        ->get();
    }

    public function getPaymentMethods()
    {
        return PaymentsMethod::leftJoin('taxes', 'payments_methods.tax_id', '=', 'taxes.id')
                        ->select(['payments_methods.id', 'payments_methods.name', 'payments_methods.price', 'taxes.tax'])
                        ->get();
    }

    public function setAdminAttributes($attributes)
    {
        $attributes['client_name'] = $this->client ? $this->client->clientName : '';

        $attributes['created'] = $this->created_at ? $this->created_at->translatedFormat('d. M \o H:i') : '';

        return $attributes;
    }

    public function isCompany()
    {
        return $this->company_name || $this->company_id || $this->company_tax_id || $this->company_vat_id;
    }

    public function getNumberAttribute()
    {
        return str_pad($this->getKey(), 6, '0', STR_PAD_LEFT);
    }

    public function getPaymentMethodPriceWithTaxAttribute()
    {
        return Store::roundNumber($this->payment_method_price * (1 + ($this->payment_method_tax/100)));
    }

    public function getDeliveryPriceWithTaxAttribute()
    {
        return Store::roundNumber($this->delivery_price * (1 + ($this->delivery_tax/100)));
    }

    public function getHash()
    {
        return sha1(env('APP_KEY').$this->getKey().'XL');
    }
}