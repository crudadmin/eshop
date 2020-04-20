<?php

namespace AdminEshop\Models\Products;

use AdminEshop\Eloquent\Concerns\CanBeInCart;
use AdminEshop\Eloquent\Concerns\HasCart;
use AdminEshop\Eloquent\Concerns\HasProductImage;
use AdminEshop\Eloquent\Concerns\HasWarehouse;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class Product extends AdminModel implements CanBeInCart
{
    use HasProductImage,
        HasWarehouse,
        PriceMutator,
        HasCart;

    /**
     * Model constructor
     *
     * @param  array  $options
     */
    public function __construct(array $options = [])
    {
        $this->append($this->getPriceAttributes());
        $this->append($this->getStockAttributes());

        parent::__construct($options);
    }

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 17:47:15';

    /*
     * Template name
     */
    protected $name = 'Produkty';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'products';

    protected $sluggable = 'name';

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
            Group::tab([
                Group::fields([
                    'name' => 'name:Názov produktu|index|limit:30|required',
                    'product_type' => 'name:Typ produktu|type:select|option:name|required',
                ])->inline(),
                'image' => 'name:Obrázok|type:file|image',
                Group::fields([
                    'ean' => 'name:EAN|hidden',
                    'code' => 'name:Kód produktu',
                ])->inline(),
            ])->icon('fa-pencil'),
            'Cena' => Group::tab([
                'Cena' => Group::fields([
                    'tax' => 'name:Sazba DPH|belongsTo:taxes,:name (:tax%)|defaultByOption:default,1|required|canAdd|hidden',
                    'price' => 'name:Cena bez DPH|type:decimal|default:0|component:PriceField|positivePriceIfRequired:products|required_if:product_type,'.implode(',', Store::orderableProductTypes()),
                ])->id('price')->width(8),
                'Zľava' => Group::fields([
                    'discount_operator' => 'name:Typ zľavy|type:select|required_with:discount|hidden',
                    'discount' => 'name:Výška zľavy|type:decimal|hideFieldIfIn:discount_operator,NULL,default|required_if:discount_operator,'.implode(',', array_keys(operator_types())).'|hidden',
                ])->id('discount')->width(4),
            ])->icon('fa-money'),
            'Popis' => Group::tab([
                'description' => 'name:Popis produktu|type:editor|hidden',
            ])->icon('fa-file-text-o'),
            'Sklad' => Group::tab([
                'warehouse_quantity' => 'name:Sklad|type:integer|default:0',
                'warehouse_type' => 'name:Možnosti skladu|default:show|type:select|index',
                'warehouse_sold' => 'name:Text dostupnosti tovaru pri vypredaní|hideFromFormIfNot:warehouse_type,everytime'
            ])->icon('fa-bars')->add('hidden'),
            'Ostatné nastavenia' => Group::tab([
                'created_at' => 'name:Vytvorené dňa|default:CURRENT_TIMESTAMP|type:datetime|disabled',
                'published_at' => 'name:Publikovať od|default:CURRENT_TIMESTAMP|type:datetime',
            ])->id('otherSettings')->icon('fa-gear'),
        ];
    }

    public function options()
    {
        return [
            'tax_id' => Store::getTaxes(),
            'product_type' => config('admineshop.product_types', []),
            'discount_operator' => [ 'default' => 'Žiadna zľava' ] + operator_types(),
            'warehouse_type' => [
                'show' => 'Zobraziť vždy s možnosťou objednania len ak je skladom',
                'everytime' => 'Zobrazit a objednat vždy, bez ohľadu na sklad',
                'hide' => 'Zobrazit a mať možnost objednat len ak je skladom',
            ],
        ];
    }

    protected $settings = [
        'title.insert' => 'Nový produkt',
        'title.update' => ':name',
        'grid.default' => 'full',
    ];

    protected $layouts = [
        'form-top' => 'setProductTabs',
    ];

    /*
     * This items will be selected from db for cart items
     */
    protected $cartSelect = [
        'id', 'slug', 'name', 'price', 'tax_id', 'code', 'warehouse_quantity', 'warehouse_type', 'warehouse_sold',
        'discount_operator', 'discount',
    ];

    public function scopeNonVariantProducts($query)
    {
        $query->whereIn('product_type', Store::nonVariantsProductTypes());
    }

    public function scopeOrderableProducts($query)
    {
        $query->whereIn('product_type', Store::orderableProductTypes());
    }

    public function scopeNonOrderableProducts($query)
    {
        $query->whereNotIn('product_type', Store::orderableProductTypes());
    }

    /**
     * Check if product is given type
     *
     * @param  string  $type
     * @return bool
     */
    public function isType($type)
    {
        if ( ! array_key_exists($type, config('admineshop.product_types')) ) {
            abort(500, 'Type '.$type.' does not exists.');
        }

        return $this->product_type == $type;
    }
}