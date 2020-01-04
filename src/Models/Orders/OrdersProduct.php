<?php

namespace AdminEshop\Models\Orders;

use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Fields\Group;
use AdminEshop\Traits\ProductTrait;
use AdminEshop\Models\Products\ProductsVariant;
use Store;
use Basket;

class OrdersProduct extends AdminModel
{
    use ProductTrait;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 06:49:15';

    /*
     * Template name
     */
    protected $name = 'Produkty k objednávce';

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

    protected $inTab = true;

    protected $publishable = false;
    protected $sortable = false;

    private $product_item = null;

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
                'variant' => 'name:Varianta produktu|belongsTo:products_variants,value|filterBy:product|required_with_values|hidden',
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

    public function settings()
    {
        return [
            'title.insert' => 'Nový produkt k objednávce',
            'title.update' => 'Upravujete produkt v objednávce',
            'grid.hidden' => true,
            'columns.id.hidden' => true,
            'columns.total' => [
                'title' => 'Cena celkem',
                'after' => 'price_tax',
            ],

            //Add currency after columns
            'columns.tax.add_after' => ' %',
            'columns.total.add_after' => ' '.Basket::getCurrency(),
            'columns.price.add_after' => ' '.Basket::getCurrency(),
            'columns.price_tax.add_after' => ' '.Basket::getCurrency(),
        ];
    }

    public function getAdminAttributes()
    {
        $attributes = parent::getAdminAttributes();

        $attributes['total'] = Store::roundNumber($this->price_tax * $this->quantity);

        return $attributes;
    }

    protected $rules = [
        \AdminEshop\Rules\OnUpdateOrderProduct::class,
    ];

    public function onUpdate()
    {
        $this->order->calculatePrices();
    }

    public function onCreate()
    {
        $this->order->calculatePrices();
    }

    public function onDelete()
    {
        $this->order->calculatePrices();
    }

    public function options()
    {
        return [
            'variant' => $this->getVariantsOptions(),
        ];
    }

    /*
     * Get product/attribute relationship
     */
    public function getProduct()
    {
        if ( $this->product_item )
            return $this->product_item;

        //Bind product or variant for uncounting from warehouse
        if ( $this->variant_id )
            $this->product_item = $this->variant;
        else
            $this->product_item = $this->product;

        return $this->product_item;
    }

    public function getPriceWithoutTaxAttribute()
    {
        return $this->price;
    }
}