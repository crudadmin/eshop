<?php

namespace AdminEshop\Eloquent\Concerns;

use OrderService;
use AdminEshop\Contracts\Discounts\DiscountCode;
use Admin\Fields\Group;
use Discounts;
use Admin;
use Store;

trait HasOrderFields
{
    /**
     * Order helper fields for features support
     *
     * @return  Group
     */
    public function getOrderHelperFields()
    {
        return Group::fields(array_merge(
            [
                'number' => 'name:Č. obj.|max:20|index|hideFromForm',
                'number_prefix' => 'name:Number prefix|type:string|max:10|inaccessible',
                'client' => 'name:Klient|belongsTo:clients|inaccessible',
                'discount_data' => 'name:Uložené serializované zľavy pri vytvárani objednávky|type:json|inaccessible',
                'currency' => 'name:Mena|belongsTo:currencies,:name :char|hidden|removeFromForm',
            ],
            //Depreaced, can be removed in v4
            config('admineshop.delivery.packeta', false)
                ? ['packeta_point' => 'name:Packeta point|type:json|inaccessible'] : [],
        ));
    }

    /**
     * Billing user details
     *
     * @return  Group
     */
    protected function getBillingFields()
    {
        $requiredRule = $this->getRequiredRuleForBilling();

        return Group::fields([
            'username' => 'name:Meno a priezvisko|inaccessible_column'.(config('admineshop.client.username_splitted') ? '|removeFromForm' : $requiredRule),
            Group::inline([
                'firstname' => 'name:Meno',
                'lastname' => 'name:Priezvisko',
            ])->add('hidden|inaccessible_column'.(!config('admineshop.client.username_splitted') ? '|removeFromForm' : $requiredRule))->attributes(!config('admineshop.client.username_splitted') ? 'hideFromForm' : ''),
            'email' => 'name:Email|required|email|hidden',
            'phone' => 'name:Telefón|'.phoneValidatorRule().'|hidden',
            'street' => 'name:Ulica a č.p.|column_name:Ulica'.$requiredRule.'|hidden',
            'city' => 'name:Mesto'.$requiredRule.'|hidden',
            'zipcode' => 'name:PSČ|zipcode'.$requiredRule.'|hidden',
            'country' => 'name:Krajina|hidden|belongsTo:countries,name|defaultByOption:default,1'.$requiredRule.'|exists:countries,id',
        ])->name('Fakturačné údaje')->id('billing')->grid(4);
    }

    /**
     * Delivery details
     *
     * @return  Group
     */
    protected function getDeliveryFields()
    {
        $requiredRule = $this->getRequiredRuleForDelivery();

        return Group::fields([
            'delivery_different' => 'name:Doručiť na inú ako fakturačnú adresu|column_name:Ina doruč. adr.|type:checkbox|default:0',
            Group::fields([
                'delivery_username' => 'name:Meno a priezvisko / Firma|inaccessible_column'.(config('admineshop.client.username_splitted') ? '|removeFromForm' : $requiredRule),
                Group::inline([
                    'delivery_firstname' => 'name:Meno',
                    'delivery_lastname' => 'name:Priezvisko',
                ])->add('hidden|inaccessible_column'.(!config('admineshop.client.username_splitted') ? '|removeFromForm' : $requiredRule))->attributes(!config('admineshop.client.username_splitted') ? 'hideFromForm' : ''),
                'delivery_phone' => 'name:Telefón|'.phoneValidatorRule(),
                'delivery_street' => 'name:Ulica a č.p.'.$requiredRule,
                'delivery_city' => 'name:Mesto'.$requiredRule,
                'delivery_zipcode' => 'name:PSČ'.$requiredRule.'|zipcode',
                'delivery_country' => 'name:Krajina|belongsTo:countries,name|exists:countries,id|defaultByOption:default,1'.$requiredRule,
            ])->attributes('hideFieldIfNot:delivery_different,1')->id('delivery_fields'),
        ])->add('hidden')->name('Dodacie údaje')->id('delivery')->grid(4);
    }

    /**
     * Company details
     *
     * @return  Group
     */
    protected function getCompanyFields()
    {
        return Group::fields([
            'is_company' => 'name:Nákup na firmu|type:checkbox|default:0',
            Group::fields([
                'company_name' => 'name:Názov firmy|required_if_checked:is_company',
                'company_id' => 'name:IČ|company_id|required_if_checked:is_company',
                'company_tax_id' => 'name:DIČ|required_if_checked:is_company',
                'company_vat_id' => 'name:IČ DPH',
            ])->attributes('hideFieldIfNot:is_company,1')->id('company_fields'),
        ])->add('hidden')->name('Firemné údaje')->grid(4);
    }

    /**
     * Additional order details
     *
     * @return  Group
     */
    protected function getAdditionalFields()
    {
        return Group::fields([
            Group::fields([
                'note' => 'name:Poznámka|type:text|hidden',
                'internal_note' => 'name:Interná poznámka|type:text|hidden',
            ])->inline(),
            Group::fields(array_merge(
                config('admineshop.order.status', true)
                    ? [ 'status' => 'name:Stav objednávky|column_name:Stav|belongsTo:orders_statuses,name|defaultByOption:default,1|title:Pri zmene stavu sa môže odosielať email zákazníkovy|sub_component:IgnoreStatusEmail|required' ] : []
                , [
                    'delivery_status' => 'name:Status dopravnej služby|type:select|default:new|hidden',
                    'delivery_identifier' => 'name:Identifikačné číslo balíka|hidden',
                ],
                config('admineshop.delivery.labels')
                    ? [ 'delivery_label' => 'name:Štítok|type:file|extensions:jpg,pdf,png|hidden' ]
                    : []
            ))->inline(),
        ])->id('additional')->name('Nastavenia objednávky');
    }

    /**
     * Shipping and payment details
     *
     * @return  Group
     */
    protected function getShippingAndPaymentFields()
    {
        return Group::tab(array_merge(
            config('admineshop.delivery.enabled', true) ? [
                'Doprava' => Group::fields([
                    Group::inline(array_merge(
                        [
                            'delivery' => 'name:Doprava|belongsTo:deliveries,name|required',
                            'delivery_data' => 'name:Dáta dopravy|type:json|inaccessible',
                            'delivery_pickup_point' => 'name:Odberné miesto|imaginary|disabled|removeFromFormIf:delivery_pickup_point,NULL'
                        ],
                        config('admineshop.delivery.multiple_locations.enabled', false)
                            ? ['delivery_location' => 'name:Predajňa|hidden|hideFromFormIfNot:delivery_id.multiple_locations,TRUE|belongsTo:'.config('admineshop.delivery.multiple_locations.table').','.config('admineshop.delivery.multiple_locations.field_name')] : []
                    )),
                    Group::inline([
                        'delivery_manual' => 'name:Manuálna cena|hidden|type:checkbox|default:0|tooltip:Ak je manuálna cena zapnutá, nebude na cenu dopravy pôsobiť žiadna automatická zľava.',
                        'delivery_vat' => 'name:DPH dopravy %|readonlyIf:delivery_manual,0|fillBy:delivery.vat|required|hidden|type:select|default:'.Store::getDefaultVat(),
                    ]),
                    'delivery_price' => 'name:Cena za dopravu|readonlyIf:delivery_manual,0|required|fillBy:delivery.price|type:decimal|component:PriceField|column_component:CurrencyPriceColumn|hidden',
                    'delivery_price_vat' => 'name:Cena za dopravu s DPH|required|column_component:CurrencyPriceColumn|hidden|removeFromForm',
                ])->id('delivery'),
            ] : [],
            config('admineshop.payment_methods.enabled', true) ? [
                'Platobná metóda' => Group::fields([
                    Group::fields([
                        'payment_method' => 'name:Platobná metóda|column_name:Platba|required|belongsTo:payments_methods,name',
                        'payment_method_vat' => 'name:DPH plat. metody %|readonlyIf:delivery_manual,0|fillBy:payment_method.vat|hidden|required|type:select|default:'.Store::getDefaultVat(),
                        'payment_method_manual' => 'name:Manuálna cena|hidden|type:checkbox|default:0|tooltip:Ak je manuálna cena zapnutá, nebude na poplatok za platobnú metódu pôsobiť žiadna automatická zľava.',
                    ])->inline(),
                    'payment_method_price' => 'name:Cena plat. metódy|readonlyIf:payment_method_manual,0|type:decimal|required|fillBy:payment_method.price|component:PriceField|column_component:CurrencyPriceColumn|hidden',
                    'payment_method_price_vat' => 'name:Cena plat. metódy s DPH|type:decimal|required|column_component:CurrencyPriceColumn|hidden|removeFromForm',
                ])->id('payment')
            ] : [],
        ))->id('shippingAndPayments')->inline()->icon('fa-truck')->name('Doprava a platba');
    }

    /**
     * Order price fields for features support
     *
     * @return  Group
     */
    public function getPriceFields()
    {
        return Group::fields([
            'Cena objednávky' => Group::half([
                'price' => 'name:Cena bez DPH|disabled|type:decimal|column_component:CurrencyPriceColumn',
                'price_vat' => 'name:Cena s DPH|disabled|type:decimal|column_name:Suma obj.|column_component:CurrencyPriceColumn',
                'paid_at' => 'name:Zaplatené dňa|type:datetime|hidden',
            ])->id('price')->inline(),
            'Zľavy' => Group::half(array_merge(
                Discounts::isRegistredDiscount(DiscountCode::class)
                    ? ['discount_codes' => 'name:Zľavové kódy|belongsToMany:discounts_codes,code|hidden|canAdd'] : []
            ))->id('discounts')->inline(),
        ])->id('orderPrices');
    }

    protected function getRequiredRuleForBilling()
    {
        return Admin::isAdmin() === true || OrderService::isDeliveryAddressPrimary() === false
            ? '|required'
            : '|required_if_checked:delivery_different';
    }

    protected function getRequiredRuleForDelivery()
    {
        return OrderService::isDeliveryAddressPrimary() === true && Admin::isFrontend() === true
                ? '|required'
                : '|required_if_checked:delivery_different';
    }
}