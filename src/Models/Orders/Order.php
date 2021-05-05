<?php

namespace AdminEshop\Models\Orders;

use AdminEshop\Admin\Buttons\GenerateInvoice;
use AdminEshop\Admin\Buttons\OrderMessagesButton;
use AdminEshop\Admin\Buttons\SendShippmentButton;
use AdminEshop\Admin\Rules\RebuildOrder;
use AdminEshop\Contracts\Discounts\DiscountCode;
use AdminEshop\Eloquent\Concerns\HasOrderHashes;
use AdminEshop\Eloquent\Concerns\OrderTrait;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Store\Country;
use AdminEshop\Models\Store\PaymentsMethod;
use AdminEshop\Requests\SubmitOrderRequest;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Discounts;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use OrderService;
use Store;

class Order extends AdminModel
{
    use Notifiable,
        OrderTrait,
        HasOrderHashes;

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

    protected $publishable = false;

    protected $sortable = false;

    protected $buttons = [
        GenerateInvoice::class,
        SendShippmentButton::class,
        OrderMessagesButton::class,
    ];

    protected $rules = [
        RebuildOrder::class,
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
            'client' => 'name:Klient|belongsTo:clients|invisible',
            'Fakturačné údaje' => Group::fields([
                'username' => 'name:Meno a priezvisko|required|hidden',
                'email' => 'name:Email|email|required',
                'phone' => 'name:Telefón|'.phoneValidatorRule().'|hidden',
                'street' => 'name:Ulica a č.p.|column_name:Ulica|required|hidden',
                'city' => 'name:Mesto|required|hidden',
                'zipcode' => 'name:PSČ|max:6|zipcode|required|hidden',
                'country' => 'name:Krajina|hidden|belongsTo:countries,name|defaultByOption:default,1|required|exists:countries,id',
            ])->grid(4),
            'Dodacie údaje' => Group::fields([
                'delivery_different' => 'name:Doručiť na inú adresu|type:checkbox|default:0',
                Group::fields([
                    'delivery_username' => 'name:Meno a priezvisko / Firma|required_if_checked:delivery_different',
                    'delivery_phone' => 'name:Telefón|'.phoneValidatorRule(),
                    'delivery_street' => 'name:Ulica a č.p.|required_if_checked:delivery_different',
                    'delivery_city' => 'name:Mesto|required_if_checked:delivery_different',
                    'delivery_zipcode' => 'name:PSČ|required_if_checked:delivery_different|zipcode',
                    'delivery_country' => 'name:Krajina|belongsTo:countries,name|exists:countries,id|defaultByOption:default,1|required_if_checked:delivery_different',
                ])->attributes('hideFieldIfNot:delivery_different,1')->id('delivery_fields'),
            ])->add('hidden')->grid(4),
            Group::fields([
                'Firemné údaje' => Group::fields([
                    'is_company' => 'name:Nákup na firmu|type:checkbox|default:0',
                    Group::fields([
                        'company_name' => 'name:Názov firmy|required_if_checked:is_company',
                        'company_id' => 'name:IČ|company_id|required_if_checked:is_company',
                        'company_tax_id' => 'name:DIČ|required_if_checked:is_company',
                        'company_vat_id' => 'name:IČ DPH',
                    ])->attributes('hideFieldIfNot:is_company,1')->id('company_fields'),
                ])->add('hidden'),
            ])->grid(4),
            'Nastavenia objednávky' => Group::fields([
                Group::fields([
                    'note' => 'name:Poznámka|type:text|hidden',
                    'internal_note' => 'name:Interná poznámka|type:text|hidden',
                ])->inline()
            ]),
            'Doprava' => Group::fields([
                Group::inline([
                    'delivery' => 'name:Doprava|belongsTo:deliveries,name|required',
                    'delivery_location' => 'name:Predajňa|hideFromFormIfNot:delivery_id.multiple_locations,TRUE|belongsTo:deliveries_locations,name',
                ]),
                Group::inline([
                    'delivery_manual' => 'name:Manuálna cena|hidden|type:checkbox|default:0|tooltip:Ak je manuálna cena zapnutá, nebude na cenu dopravy pôsobiť žiadna automatická zľava.',
                    'delivery_vat' => 'name:DPH dopravy %|readonlyIf:delivery_manual,0|fillBy:delivery.vat|required|hidden|type:select|default:'.Store::getDefaultVat(),
                ]),
                'delivery_price' => 'name:Cena za dopravu|readonlyIf:delivery_manual,0|required|fillBy:delivery.price|type:decimal|component:PriceField|hidden',
            ])->grid(6),
            'Platobná metóda' => Group::fields([
                Group::fields([
                    'payment_method' => 'name:Platobná metóda|column_name:Platba|required|belongsTo:payments_methods,name',
                    'payment_method_vat' => 'name:DPH plat. metody %|readonlyIf:delivery_manual,0|fillBy:payment_method.vat|hidden|required|type:select|default:'.Store::getDefaultVat(),
                    'payment_method_manual' => 'name:Manuálna cena|hidden|type:checkbox|default:0|tooltip:Ak je manuálna cena zapnutá, nebude na poplatok za platobnú metódu pôsobiť žiadna automatická zľava.',
                ])->inline(),
                'payment_method_price' => 'name:Cena plat. metódy|readonlyIf:payment_method_manual,0|type:decimal|required|fillBy:payment_method.price|component:PriceField|hidden',
            ])->grid(6),
            Group::fields([
                'Cena objednávky' => Group::fields([
                    Group::fields([
                        'status' => 'name:Stav objednávky|column_name:Stav|type:select|required|default:new',
                        'delivery_status' => 'name:Status dopravnej služby|type:select|default:new|hidden',
                        'delivery_message' => 'name:Hlásenie z dopravnej služby|invisible',
                        'delivery_identifier' => 'name:Identifikátor zvozu dopravy|invisible',
                    ])->inline(),
                    Group::fields([
                        'price' => 'name:Cena bez DPH|disabled|type:decimal',
                        'price_vat' => 'name:Cena s DPH|disabled|type:decimal',
                        'paid_at' => 'name:Zaplatené dňa|type:datetime',
                    ])->inline(),
                ])->width(6),
                'Zľavy' => Group::fields([
                    'discount_code' => 'name:Zľavový kód|belongsTo:discounts_codes,code|hidden|canAdd',
                    'discount_data' => 'name:Uložené serializované zľavy pri vytvárani objednávky|type:json|inaccessible',
                ])->width(6)->id('discounts'),
            ])
        ];
    }

    public function mutateFields($fields)
    {
        parent::mutateFields($fields);

        //Remove delivery location if multiple delivery locations are disabled
        if ( config('admineshop.delivery.multiple_locations') !== true ){
            $fields->remove(['delivery_location']);
        }

        //If discount code is not registred, we can remove it from order
        if ( Discounts::isRegistredDiscount(DiscountCode::class) === false ){
            $fields->remove(['discount_code']);
        }
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
            'table.onclickopen' => true,
            'columns.price.add_after' => ' '.Store::getCurrency(),
            'columns.price_vat.add_after' => ' '.Store::getCurrency(),
            'columns.created.name' => 'Vytvorená dňa',
            'columns.client_name' => [
                'encode' => false,
                'after' => 'id',
                'name' => 'Zákazník',
            ],
            'columns.delivery_address' => [
                'name' => 'Dodacia adresa',
                'after' => 'email',
            ],
            'columns.delivery_status_text' => [
                'encode' => false,
                'name' => 'Status dopravy',
                'after' => 'status',
            ],
        ];
    }

    public function options()
    {
        $countries = Store::getCountries();

        $vatOptions = Store::getVats()->map(function($item){
            $item->vatValue = $item->vat.'%';
            return $item;
        })->pluck('vatValue', 'vat');

        return [
            'delivery_vat' => $vatOptions,
            'payment_method_vat' => $vatOptions,
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
            'delivery_status' => [
                'new' => 'Čaká za objednanim dopravy',
                'ok' => 'Prijatá',
                'error' => 'Neprijatá (chyba)',
            ],
        ];
    }

    public function scopeAdminRows($query)
    {
        $query->with(['log']);
    }

    public function setAdminAttributes($attributes)
    {
        $attributes['client_name'] = $this->getClientName();

        $attributes['delivery_address'] = $this->getDeliveryAddress();

        $attributes['created'] = $this->created_at ? $this->created_at->translatedFormat('d.m'.($this->created_at->year == date('Y') ? '' : '.Y').' \o H:i') : '';

        $attributes['delivery_status_text'] = $this->getDeliveryStatusText();

        return $attributes;
    }

    public function getHasCompanyAttribute()
    {
        return $this->company_name || $this->company_id || $this->company_tax_id || $this->company_vat_id;
    }

    public function getNumberAttribute()
    {
        return str_pad($this->getKey(), 6, '0', STR_PAD_LEFT);
    }

    public function getPaymentMethodPriceWithVatAttribute()
    {
        return Store::roundNumber($this->payment_method_price * (1 + ($this->payment_method_vat/100)));
    }

    public function getDeliveryPriceWithVatAttribute()
    {
        return Store::roundNumber($this->delivery_price * (1 + ($this->delivery_vat/100)));
    }

    public function getStatusTextAttribute()
    {
        return $this->getOptionValue('status', $this->status);
    }

    public function getInvoiceUrlAttribute()
    {
        if ( OrderService::hasInvoices() == false ){
            return;
        }

        return ($invoice = $this->invoice->last())
                ? $invoice->getPdf()->url
                : null;
    }

    /**
     * This scope will be applied in order detail
     *
     * @param  Builder  $query
     */
    public function scopeOrderDetail($query)
    {
        $withAll = function($query){
            $query->withTrashed()->withUnpublished();
        };

        $query->with(array_filter([
            'discount_code',
            'delivery',
            config('admineshop.delivery.multiple_locations') ? 'delivery_location' : null,
            'payment_method',
            'country',
            'delivery_country',
            'items.product' => $withAll,
            'items.variant' => $withAll,
        ]));
    }

    /**
     * This scope will be applied in success order page request
     *
     * @param  Builder  $query
     */
    public function scopeOrderCreated($query)
    {

    }

    /**
     * Order response format
     *
     * @return  array
     */
    public function toResponseFormat()
    {
        return $this->append([
            'number',
            'hasCompany',
            'statusText',
            'deliveryPriceWithVat',
            'deliveryTrackingUrl',
            'paymentMethodPriceWithVat',
            'invoiceUrl',
        ]);
    }

    /**
     * We can mutate request request before validation here
     *
     * @return  Admin\Core\Fields\FieldsValidator
     */
    public function orderValidator(Request $request)
    {
        return $this->validator($request)->use(
            config('admineshop.cart.order.validator', SubmitOrderRequest::class)
        );
    }
}