<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\Discounts\DiscountCode;
use Admin\Fields\Group;
use Discounts;
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
                'number' => 'name:Č. obj.|max:20|hideFromForm',
                'number_prefix' => 'name:Number prefix|type:string|max:10|inaccessible',
                'client' => 'name:Klient|belongsTo:clients|invisible',
                'discount_data' => 'name:Uložené serializované zľavy pri vytvárani objednávky|type:json|inaccessible'
            ],
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
        return Group::fields([
            'username' => 'name:Meno a priezvisko|required|hidden',
            'email' => 'name:Email|email|required',
            'phone' => 'name:Telefón|'.phoneValidatorRule().'|hidden',
            'street' => 'name:Ulica a č.p.|column_name:Ulica|required|hidden',
            'city' => 'name:Mesto|required|hidden',
            'zipcode' => 'name:PSČ|max:6|zipcode|required|hidden',
            'country' => 'name:Krajina|hidden|belongsTo:countries,name|defaultByOption:default,1|required|exists:countries,id',
        ])->name('Fakturačné údaje')->id('billing')->grid(4);
    }

    /**
     * Delivery details
     *
     * @return  Group
     */
    protected function getDeliveryFields()
    {
        return Group::fields([
            'delivery_different' => 'name:Doručiť na inú ako fakturačnú adresu|type:checkbox|default:0',
            Group::fields([
                'delivery_username' => 'name:Meno a priezvisko / Firma|required_if_checked:delivery_different',
                'delivery_phone' => 'name:Telefón|'.phoneValidatorRule(),
                'delivery_street' => 'name:Ulica a č.p.|required_if_checked:delivery_different',
                'delivery_city' => 'name:Mesto|required_if_checked:delivery_different',
                'delivery_zipcode' => 'name:PSČ|required_if_checked:delivery_different|zipcode',
                'delivery_country' => 'name:Krajina|belongsTo:countries,name|exists:countries,id|defaultByOption:default,1|required_if_checked:delivery_different',
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
            Group::fields([
                'status' => 'name:Stav objednávky|column_name:Stav|type:select|required|default:new',
                'delivery_status' => 'name:Status dopravnej služby|type:select|default:new|hidden',
                'delivery_identifier' => 'name:Identifikátor zvozu dopravy|invisible',
            ])->inline(),
        ])->id('additional')->name('Nastavenia objednávky');
    }

    /**
     * Shipping and payment details
     *
     * @return  Group
     */
    protected function getShippingAndPaymentFields()
    {
        return Group::fields([
            'Doprava' => Group::fields([
                Group::inline(array_merge(
                    ['delivery' => 'name:Doprava|belongsTo:deliveries,name|required'],
                    config('admineshop.delivery.multiple_locations', false)
                        ? ['delivery_location' => 'name:Predajňa|hideFromFormIfNot:delivery_id.multiple_locations,TRUE|belongsTo:deliveries_locations,name'] : []
                )),
                Group::inline([
                    'delivery_manual' => 'name:Manuálna cena|hidden|type:checkbox|default:0|tooltip:Ak je manuálna cena zapnutá, nebude na cenu dopravy pôsobiť žiadna automatická zľava.',
                    'delivery_vat' => 'name:DPH dopravy %|readonlyIf:delivery_manual,0|fillBy:delivery.vat|required|hidden|type:select|default:'.Store::getDefaultVat(),
                ]),
                'delivery_price' => 'name:Cena za dopravu|readonlyIf:delivery_manual,0|required|fillBy:delivery.price|type:decimal|component:PriceField|hidden',
            ])->id('delivery')->if(config('admineshop.delivery.enabled', true)),
            'Platobná metóda' => Group::fields([
                Group::fields([
                    'payment_method' => 'name:Platobná metóda|column_name:Platba|required|belongsTo:payments_methods,name',
                    'payment_method_vat' => 'name:DPH plat. metody %|readonlyIf:delivery_manual,0|fillBy:payment_method.vat|hidden|required|type:select|default:'.Store::getDefaultVat(),
                    'payment_method_manual' => 'name:Manuálna cena|hidden|type:checkbox|default:0|tooltip:Ak je manuálna cena zapnutá, nebude na poplatok za platobnú metódu pôsobiť žiadna automatická zľava.',
                ])->inline(),
                'payment_method_price' => 'name:Cena plat. metódy|readonlyIf:payment_method_manual,0|type:decimal|required|fillBy:payment_method.price|component:PriceField|hidden',
            ])->id('payment')->if(config('admineshop.payments_methods.enabled', true))
        ])->id('shippingAndPayments')->inline();
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
                'price' => 'name:Cena bez DPH|disabled|type:decimal',
                'price_vat' => 'name:Cena s DPH|disabled|type:decimal',
                'paid_at' => 'name:Zaplatené dňa|type:datetime',
            ])->id('price')->inline(),
            'Zľavy' => Group::half(array_merge(
                Discounts::isRegistredDiscount(DiscountCode::class)
                    ? ['discount_code' => 'name:Zľavový kód|belongsTo:discounts_codes,code|hidden|canAdd'] : []
            ))->id('discounts')->inline(),
        ])->inline()->id('orderPrices');
    }
}