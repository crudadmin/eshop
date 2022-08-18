<?php

namespace AdminEshop\Models\Orders;

use AdminEshop\Admin\Buttons\GenerateInvoice;
use AdminEshop\Admin\Buttons\OrderMessagesButton;
use AdminEshop\Admin\Buttons\SendShippmentButton;
use AdminEshop\Admin\Rules\OnOrderStatusChange;
use AdminEshop\Admin\Rules\OrderNumber;
use AdminEshop\Admin\Rules\RebuildOrder;
use AdminEshop\Eloquent\Concerns\HasOrderEmails;
use AdminEshop\Eloquent\Concerns\HasOrderFields;
use AdminEshop\Eloquent\Concerns\HasOrderHashes;
use AdminEshop\Eloquent\Concerns\HasOrderInvoice;
use AdminEshop\Eloquent\Concerns\HasOrderLog;
use AdminEshop\Eloquent\Concerns\HasOrderNumber;
use AdminEshop\Eloquent\Concerns\HasUsernames;
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
        HasUsernames,
        HasOrderInvoice,
        HasOrderHashes,
        HasOrderEmails,
        HasOrderNumber,
        HasOrderLog,
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
        OnOrderStatusChange::class,
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
        $settings = [
            'autoreset' => false,
            'increments' => false,
            'title.insert' => 'Nová objednávka',
            'buttons.insert' => 'Vytvoriť novú objednávku',
            'title.update' => 'Objednávka č. :number - :created',
            'grid.enabled' => false,
            'grid.default' => 'full',
            'columns.price.hidden' => true,
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
                'after' => 'client_name',
                'encode' => false,
            ],
            'columns.delivery_status' => [
                'encode' => false,
                'name' => 'Status dopravy',
                'before' => 'delivery_id',
            ],
            'columns.is_paid' => [
                'encode' => false,
                'name' => 'Zaplatené',
                'after' => 'price_vat',
            ],
            'columns.items_list' => [
                'component' => 'OrderItemsColumn',
                'name' => 'Položky',
                'before' => 'price_vat',
            ],
        ];

        if ( config('admineshop.order.status', true) ){
            $settings['columns.status_id'] = [
                'after' => 'is_paid',
                'encode' => false,
            ];
        }

        return $settings;
    }

    public function options()
    {
        $countries = Store::getCountries();

        $options = [
            'country_id' => $countries,
            'delivery_status' => [
                'new' => _('Čaká za objednanim dopravy'),
                'ok' => _('Prijatá'),
                'sent' => _('Odoslaná'),
                'error' => _('Neprijatá (chyba)'),
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
        if ( config('admineshop.payment_methods.enabled', true) === true ) {
            $options = array_merge($options, [
                'payment_method_vat' => $this->getVatOptions(),
                'payment_method_id' => $this->getPaymentMethods(),
            ]);
        }

        return $options;
    }

    public function scopeAdminRows($query)
    {
        $query->with(['log', 'items']);
    }

    public function setAdminAttributes($attributes)
    {
        $attributes['delivery_pickup_point'] = $this->getPickupAddressWithName();

        return $attributes;
    }

    public function setAdminRowsAttributes($attributes)
    {
        $attributes['$indicator'] = $this->getOrderIndicator();
        $attributes['number'] = $this->number;
        $attributes['client_name'] = $this->getClientName();
        $attributes['delivery_address'] = $this->getDeliveryAddress();
        $attributes['created'] = $this->created_at ? sprintf(_('%s o %s'), $this->created_at->translatedFormat('d.m'.($this->created_at->year == date('Y') ? '' : '.Y')), $this->created_at->format('H:i')) : '';
        $attributes['status_id'] = $this->getStatusColumn();
        $attributes['delivery_status'] = $this->getDeliveryStatusColumn();
        $attributes['is_paid'] = $this->getIsPaidStatusColumn();
        $attributes['items_list'] = $this->items->count();

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
        return $this->status?->name;
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
            $this->getField('status_id') ? 'status' : null,
            $this->getField('discount_code_id') ? 'discount_code' : null,
            $this->getField('delivery_id') ? 'delivery' : null,
            $this->getField('delivery_location_id') ? 'delivery_location' : null,
            $this->getField('payment_method_id') ? 'payment_method' : null,
            $this->getField('country_id') ? 'country' : null,
            $this->getField('delivery_country_id') ? 'delivery_country' : null,
            'items.product' => $withAll,
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

    public function scopeWithClientListingResponse($query)
    {

    }

    public function setOrderResponse()
    {
        return $this;
    }

    /**
     * Order response format
     *
     * @return  array
     */
    public function setClientListingResponse()
    {
        if ( $this->relationLoaded('items') ) {
            $this->items->each->setClientListingResponse();
        }

        if ( $this->relationLoaded('status') ) {
            $this->status->setVisible(['id', 'name', 'color', 'default']);
        }

        return $this->append([
            'number',
            'hasCompany',
            'statusText',
            'deliveryPriceWithVat',
            'deliveryTrackingUrl',
            'paymentMethodPriceWithVat',
            'invoiceUrl',
            'deliveryPickupName',
            'deliveryPickupAddress',
        ]);
    }

    public function setSuccessOrderFormat()
    {
        return $this;
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