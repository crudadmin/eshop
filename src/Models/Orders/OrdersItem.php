<?php

namespace AdminEshop\Models\Orders;

use Admin;
use AdminEshop\Admin\Rules\AddMissingPrices;
use AdminEshop\Admin\Rules\BindDefaultPrice;
use AdminEshop\Admin\Rules\BindIdentifierName;
use AdminEshop\Admin\Rules\RebuildOrderOnItemChange;
use AdminEshop\Admin\Rules\ReloadProductQuantity;
use AdminEshop\Contracts\Cart\Identifiers\Concerns\IdentifierSupport;
use AdminEshop\Contracts\Cart\Identifiers\Concerns\UsesIdentifier;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class OrdersItem extends AdminModel implements UsesIdentifier
{
    use PriceMutator,
        IdentifierSupport;

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

    /*
     * Model Parent
     * Eg. Articles::class,
     */
    protected $belongsToModel = Order::class;

    protected $withoutParent = true;

    protected $publishable = false;

    protected $sortable = false;

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
                'product' => 'name:Produkt|belongsTo:products,name|required_without:manual_price|limit:50|max:90',
                'quantity' => 'name:Množstvo|min:1|max:9999|default:1|type:integer|required',
            ]),
            Group::half([
                'variant' => 'name:Varianta produktu|belongsTo:products_variants,name|filterBy:product|required_with_values|hidden',
                'variant_text' => 'name:Popis položky|tooltip:Slúži pre položky bez priradeného produktu',
            ]),
            Group::fields([
                'default_price' => 'name:Pôvodna cena bez DPH|invisible|type:decimal|title:Cena produktu v čase objednania.|disabled',
                'price' => 'name:Cena/j bez DPH|type:decimal|required_if:manual_price,1|disabledIf:manual_price,0',
                'tax' => 'name:DPH %|type:decimal|required_if:manual_price,1|disabledIf:manual_price,0',
                'price_tax' => 'name:Cena/j s DPH|type:decimal|required_if:manual_price,1|disabledIf:manual_price,0',
                'manual_price' => 'name:Manuálna cena|default:0|tooltip:Ak je manuálna cena zapnutá, nebude na cenu pôsobiť žiadna automatická zľava.|type:checkbox',
            ])->inline()
        ];
    }

    protected $inMenu = true;

    public function options()
    {
        return [
            'product_id' => $this->getAvailableProducts(),
            'variant_id' => $this->getAvailableVariants(),
        ];
    }

    protected $layouts = [
        'form-top' => [
            'recalculateTaxPrices',
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

    public function settings()
    {
        return [
            'increments' => false,
            'title.insert' => 'Nová položka',
            'title.update' => 'Upravujete položku v objednávke',
            'grid.default' => 'full',
            'grid.disabled' => true,
            'columns.total' => [
                'title' => 'Cena spolu',
                'after' => 'price_tax',
            ],

            //Add currency after columns
            'columns.price_tax.add_after' => ' '.Store::getCurrency(),
        ];
    }

    public function setAdminAttributes($attributes)
    {
        $attributes['total'] = Store::priceFormat($this->price_tax * $this->quantity);

        return $attributes;
    }

    /*
     * Skip variants non orderable variants
     * Product can also have variants, bud this variants may not be orderable. We want skip this variants.
     *
     * @return Collection
     */
    public function getAvailableVariants()
    {
        return ProductsVariant::select(['id', 'product_id', 'name', 'price', 'tax_id', 'discount_operator', 'discount'])
                ->whereHas('product', function($query){
                    $query->whereIn('product_type', Store::filterConfig('orderableVariants', true));
                })->get()->map(function($item){
                    $item->setVisible(['id', 'product_id', 'name', 'priceWithTax', 'priceWithoutTax', 'taxValue'])
                         ->setAppends(['priceWithTax', 'priceWithoutTax', 'taxValue']);

                    return $item;
                });
    }

    /**
     * Return products with needed attributes
     *
     * @return  Collection
     */
    public function getAvailableProducts()
    {
        return Product::select(['id', 'name', 'price', 'tax_id', 'discount_operator', 'discount'])
                ->get()->map(function($item){
                    $item->setVisible(['id', 'name', 'priceWithTax', 'priceWithoutTax', 'taxValue'])
                         ->setAppends(['priceWithTax', 'priceWithoutTax', 'taxValue']);

                    return $item;
                });
    }

    /*
     * Get product/attribute relationship
     */
    public function getProduct()
    {
        return Admin::cache('ordersItems.'.$this->getKey(), function(){
            //Bind product or variant for uncounting from warehouse
            if ( $this->variant_id )
                return $this->variant;

            return $this->product;
        });
    }

    /**
     * Returns if cart item has manual price
     *
     * @return  bool
     */
    public function getHasManualPriceAttribute()
    {
        return $this->manual_price === true;
    }

    /*
     * Set initial price for discounts
     */
    public function getInitialPriceWithoutTaxAttribute()
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
}