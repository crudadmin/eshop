<?php

namespace AdminEshop\Models\Orders;

use Admin;
use AdminEshop\Admin\Rules\OnUpdateOrderProduct;
use AdminEshop\Admin\Rules\ReloadProductQuantity;
use AdminEshop\Models\Products\ProductsVariant;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class OrdersItem extends AdminModel
{
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
                'product' => 'name:Produkt|belongsTo:products,name|limit:50|max:90',
                'quantity' => 'name:Množstvo|min:1|max:9999|default:1|type:integer|required',
            ]),
            Group::half([
                'variant' => 'name:Varianta produktu|belongsTo:products_variants,name|filterBy:product|required_with_values|hidden',
                'variant_text' => 'name:Popis varianty',
            ]),
            Group::fields([
                Group::third([
                    'price' => 'name:Cena/j bez DPH|title:Pri prázdnej hodnote sa vyplní podľa produktu|required_without:product_id|type:decimal',
                ]),
                Group::third([
                    'tax' => 'name:DPH %|title:Pri prázdnej hodnote sa vyplní podľa produktu|type:decimal',
                ]),
                Group::third([
                    'price_tax' => 'name:Cena/j s DPH|title:Pri prázdnej hodnote sa vypočíta|required_without:product_id|type:decimal',
                ])
            ])
        ];
    }

    public function options()
    {
        return [
            'variant_id' => $this->getAvailableVariants(),
        ];
    }

    /*
     * Skip variants non orderable variants
     * Product can also have variants, bud this variants may not be orderable. We want skip this variants.
     */
    public function getAvailableVariants()
    {
        return ProductsVariant::select(['id', 'product_id', 'name'])->whereHas('product', function($query){
            $query->whereIn('product_type', Store::filterConfig('orderableVariants', true));
        })->get();
    }

    public function settings()
    {
        return [
            'increments' => false,
            'title.insert' => 'Nová položka',
            'title.update' => 'Upravujete položku v objednávke',
            'grid.hidden' => true,
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

    protected $rules = [
        OnUpdateOrderProduct::class,
        ReloadProductQuantity::class,
    ];

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

    public function getPriceWithoutTaxAttribute()
    {
        return $this->price;
    }
}