<?php

namespace AdminEshop\Models\Orders;

use Admin;
use AdminEshop\Admin\Rules\AddMissingPrices;
use AdminEshop\Admin\Rules\BindDefaultPrice;
use AdminEshop\Admin\Rules\BindIdentifierName;
use AdminEshop\Admin\Rules\RebuildOrderOnItemChange;
use AdminEshop\Admin\Rules\ReloadProductQuantity;
use AdminEshop\Contracts\Cart\Concerns\HasOptionableDiscounts;
use AdminEshop\Contracts\Cart\Identifiers\Concerns\IdentifierSupport;
use AdminEshop\Contracts\Cart\Identifiers\Concerns\UsesIdentifier;
use AdminEshop\Contracts\Cart\Identifiers\DefaultIdentifier;
use AdminEshop\Contracts\Order\Concerns\HasOrderItemNames;
use AdminEshop\Eloquent\Concerns\DiscountHelper;
use AdminEshop\Eloquent\Concerns\DiscountSupport;
use AdminEshop\Eloquent\Concerns\OrderItemTrait;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class OrdersItem extends AdminModel implements UsesIdentifier, DiscountSupport
{
    use PriceMutator,
        IdentifierSupport,
        DiscountHelper,
        HasOptionableDiscounts,
        HasOrderItemNames,
        OrderItemTrait;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 06:49:15';

    /*
     * Template name
     */
    protected $name = 'Produkty k objednávke';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $withoutParent = true;

    protected $publishable = false;

    protected $sortable = false;

    protected $reversed = true;

    protected $icon = 'fa-shopping-basket';

    protected $layouts = [
        'form-top' => [
            'recalculateVatPrices',
            'setPricesFromProduct'
        ],
    ];

    protected $rules = [
        BindDefaultPrice::class,
        BindIdentifierName::class,
        AddMissingPrices::class,
        ReloadProductQuantity::class,
        RebuildOrderOnItemChange::class, //We need reload order prices after quantity check
    ];

    public function belongsToModel()
    {
        return get_class(Admin::getModel('Order'));
    }

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            Group::half([
                'identifier' => 'name:Cart identifier|invisible|index',
                'product' => 'name:Produkt|belongsTo:products,name|required_without:manual_price|canEdit|disabledIf:identifier,discount|limit:50|hidden',
                'quantity' => 'name:Množstvo|min:1|max:9999|default:1|type:integer|required',
            ])->id('itemPrimary'),
            Group::half([
                'name' => 'name:Popis položky|tooltip:Slúži pre položky bez priradeného produktu|hidden',
                'order_item' => 'name:Patrí k položke|belongsTo:orders_items,id|inaccessible',
            ])->id('itemAdditional'),
            Group::fields([
                'default_price' => 'name:Pôvodna cena bez DPH|invisible|type:decimal|title:Cena produktu v čase objednania.|disabled',
                'price' => 'name:Cena/j bez DPH|type:decimal|required_if:manual_price,1|disabledIf:manual_price,0',
                'vat' => 'name:DPH %|type:select|default:'.Store::getDefaultVat().'|required_if:manual_price,1|disabledIf:manual_price,0',
                'price_vat' => 'name:Cena/j s DPH|type:decimal|required_if:manual_price,1|disabledIf:manual_price,0',
                'manual_price' => 'name:Manuálna cena|default:0|hidden|tooltip:Ak je manuálna cena zapnutá, nebude na cenu pôsobiť žiadna automatická zľava.|type:checkbox',
                'discountable' => 'name:Povoliť zľavy na položku|type:checkbox|default:0|invisible',
            ])->inline()
        ];
    }

    public function options()
    {
        return [
            'vat' => Store::getVats()->map(function($item){
                $item->vatValue = $item->vat.'%';
                return $item;
            })->pluck('vatValue', 'vat'),
            'product_id' => $this->getAvailableProducts(),
        ];
    }

    public function settings()
    {
        return [
            // 'increments' => false,
            'buttons.insert' => 'Pridať položku',
            'buttons.create' => 'Pridať položku',
            'title.insert' => 'Pridajte položku do objednávky',
            'title.update' => 'Upravujete položku v objednávke',
            'grid.default' => 'full',
            'grid.disabled' => true,
            'columns.product_name.name' => 'Položka',
            'columns.product_name.before' => 'quantity',
            'columns.product_name.encode' => false,
            'columns.quantity.after' => 'name',
            'columns.total' => [
                'name' => 'Cena spolu',
                'after' => 'price_vat',
            ],

            //Add currency after columns
            'columns.price_vat.add_after' => ' '.Store::getCurrency(),
            'reloadOnUpdate' => true,
        ];
    }

    public function setAdminRowsAttributes($attributes)
    {
        $attributes['total'] = Store::priceFormat(
            $this->calculateVatPrice($this->price, null) * $this->quantity
        );

        $attributes['product_name'] = $this->getProductName();

        return $attributes;
    }

    public function scopeAdminRows($query)
    {
        $query->with('product.product');
    }

    /*
     * Rewrite initial price for discounts from saved initial value
     */
    public function getInitialPriceWithoutVatAttribute()
    {
        //If is manualy typed price, we need return order item price.
        //We also need return default price, if item does not have identifier with
        //discounts support. Sometimes may happend that item had discounts support in,
        //but after some time identifier may change his discounts support to false. In this
        //case we need turn off default price, and return actual price.
        if (
            $this->hasManualPrice
            || $this->getIdentifierClass()->hasDiscounts() === false
        ) {
            return Store::roundNumber($this->price);
        }

        //If default price is missing, then use price attribute
        if ( is_null($this->default_price) ) {
            throw new \Exception('Ospravelňujeme sa, nastala nečakaná chyba. Predvolená cena produktu nie je definovaná. Prosím, kontaktujte administrátora.');
        }

        //But if price is calculated dynamically, we need use default price
        return Store::roundNumber($this->default_price);
    }

    /*
     * Get product/attribute relationship
     */
    public function getProduct()
    {
        return Admin::cache('ordersItems.'.$this->getKey(), function(){
            return $this->product;
        });
    }

    public function setClientListingResponse()
    {
        return $this;
    }

    public function setSuccessOrderFormat()
    {
        return $this;
    }

    /**
     * Returns all product name information in array
     *
     * @return  array
     */
    public function getProductNameParts() : array
    {
        $array = [];

        if ( ($identifier = $this->getIdentifierClass()) ){
            $array = $identifier->getProductNameParts($this);
        }

        //Add additional order item description
        if ( $name = $this->getValue('name') ) {
            $array[] = $this->getValue('name');
        }

        return array_unique($array);
    }

    public function invoiceItemName()
    {
        $name = e($this->getProductNamePartsSections(0));

        if ( $additional = $this->getProductNamePartsSections(1) ) {
            $name .= ' - '.e($additional);
        }

        return $name;
    }
}