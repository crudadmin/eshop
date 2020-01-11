<?php

namespace AdminEshop\Models\Products;

use AdminEshop\Eloquent\Concerns\HasCart;
use AdminEshop\Eloquent\Concerns\HasProductImage;
use AdminEshop\Eloquent\Concerns\HasWarehouse;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class Product extends AdminModel
{
    use HasProductImage,
        HasWarehouse,
        PriceMutator,
        HasCart;

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
                    'type' => 'name:Typ produktu|type:select|option:name|required|default:regular',
                ])->inline(),
                'image' => 'name:Obrázok|type:file|image',
                Group::fields([
                    'ean' => 'name:EAN|hidden',
                    'code' => 'name:Kód produktu',
                ])->inline(),
            ])->icon('fa-pencil'),
            'Cena' => Group::tab([
                'Cena' => Group::fields([
                    'tax' => 'name:Sazba DPH|belongsTo:taxes,:name (:tax%)|required|canAdd|hidden',
                    'price' => 'name:Cena bez DPH|type:decimal|default:0',
                ])->width(8),
                'Zľava' => Group::fields([
                    'discount_operator' => 'name:Typ zľavy|type:select|required_with:discount|hidden',
                    'discount' => 'name:Výška zľavy|type:decimal|required_if:discount_operator,'.implode(',', array_keys(operator_types())).'|hidden',
                ])->width(4),
            ])->id('price')->icon('fa-money'),
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
            'type' => config('admineshop.product_types', []),
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

    protected $appends = [
        'initialPriceWithTax', 'initialPriceWithoutTax',
        'defaultPriceWithTax', 'defaultPriceWithoutTax',
        'priceWithTax', 'priceWithoutTax', 'clientPrice',
        'stockText', 'hasStock',
    ];

    /*
     * This items will be selected frm db for cart items
     */
    protected $cartSelect = [
        'id', 'slug', 'name', 'price', 'tax_id', 'code', 'warehouse_quantity', 'warehouse_type', 'warehouse_sold',
        'discount_operator', 'discount',
    ];
}