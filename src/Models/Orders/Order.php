<?php

namespace AdminEshop\Models\Orders;

use AdminEshop\Admin\Buttons\GenerateInvoice;
use AdminEshop\Admin\Buttons\OrderMessagesButton;
use AdminEshop\Admin\Buttons\SendShippmentButton;
use AdminEshop\Admin\Rules\OrderNumber;
use AdminEshop\Admin\Rules\RebuildOrder;
use AdminEshop\Eloquent\Concerns\HasOrderEmails;
use AdminEshop\Eloquent\Concerns\HasOrderFields;
use AdminEshop\Eloquent\Concerns\HasOrderHashes;
use AdminEshop\Eloquent\Concerns\HasOrderInvoice;
use AdminEshop\Eloquent\Concerns\HasOrderNumber;
use AdminEshop\Eloquent\Concerns\OrderPayments;
use AdminEshop\Eloquent\Concerns\OrderShipping;
use AdminEshop\Eloquent\Concerns\OrderTrait;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Store\Country;
use AdminEshop\Models\Store\PaymentsMethod;
use AdminEshop\Requests\SubmitOrderRequest;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use OrderService;
use Store;

class Order extends AdminModel
{
    use Notifiable,
        OrderTrait,
        OrderPayments,
        OrderShipping,
        HasOrderInvoice,
        HasOrderHashes,
        HasOrderEmails,
        HasOrderNumber,
        HasOrderFields;

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

    protected $rules = [
        OrderNumber::class,
        RebuildOrder::class,
    ];

    public function buttons()
    {
        return array_merge([
            GenerateInvoice::class,
            SendShippmentButton::class,
            OrderMessagesButton::class,
        ], $this->getShippingButtons());
    }

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
            $this->getOrderHelperFields(),
            $this->getBillingFields(),
            $this->getDeliveryFields(),
            $this->getCompanyFields(),
            $this->getAdditionalFields(),
            $this->getShippingAndPaymentFields(),
            $this->getPriceFields(),
        ];
    }

    public function settings()
    {
        return [
            'autoreset' => false,
            'increments' => false,
            'title.insert' => 'Nová objednávka',
            'buttons.insert' => 'Vytvoriť novú objednávku',
            'title.update' => 'Objednávka č. :number - :created',
            'grid.enabled' => false,
            'grid.default' => 'full',
            'columns.price.add_after' => ' '.Store::getCurrency(),
            'columns.price_vat.add_after' => ' '.Store::getCurrency(),
            'columns.created.name' => 'Vytvorená dňa',
            'columns.client_name' => [
                'encode' => false,
                'after' => 'number',
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

        $options = [
            'country_id' => $countries,
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

        //Add delivery feature options
        if ( config('admineshop.delivery.enabled', true) === true ) {
            $options = array_merge($options, [
                'delivery_country_id' => $countries,
                'delivery_id' => $this->getDeliveries(),
                'delivery_vat' => $this->getVatOptions(),
            ]);
        }

        //Add payment method feature options
        if ( config('admineshop.payments_methods.enabled', true) === true ) {
            $options = array_merge($options, [
                'payment_method_vat' => $this->getVatOptions(),
                'payment_method_id' => $this->getPaymentMethods(),
            ]);
        }

        return $options;
    }

    public function scopeAdminRows($query)
    {
        $query->with(['log']);
    }

    public function setAdminAttributes($attributes)
    {
        $attributes['number'] = $this->number;

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
        //We need return value from attributes, because this property may be appended also when field does exists.
        //If we would use $value from parameter, this parameter may be null
        if ( config('admineshop.cart.order.number.custom', false) === true ) {
            return $this->attributes['number'] ?? null;
        }

        //Generate order number automatically by order ID
        return str_pad($this->getKey(), config('admineshop.cart.order.number.length', 6), '0', STR_PAD_LEFT);
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
            $this->getField('discount_code_id') ? 'discount_code' : null,
            $this->getField('delivery_id') ? 'delivery' : null,
            $this->getField('delivery_location_id') ? 'delivery_location' : null,
            $this->getField('payment_method_id') ? 'payment_method' : null,
            $this->getField('country_id') ? 'country' : null,
            $this->getField('delivery_country_id') ? 'delivery_country' : null,
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