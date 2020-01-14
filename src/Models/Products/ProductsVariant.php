<?php

namespace AdminEshop\Models\Products;

use AdminEshop\Eloquent\Concerns\HasCart;
use AdminEshop\Eloquent\Concerns\HasProductAttributes;
use AdminEshop\Eloquent\Concerns\HasProductImage;
use AdminEshop\Eloquent\Concerns\HasWarehouse;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class ProductsVariant extends AdminModel
{
    use HasProductAttributes,
        HasWarehouse,
        HasProductImage,
        PriceMutator,
        HasCart;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-12 17:33:15';

    /*
     * Template name
     */
    protected $name = 'Varianty produktu';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $inTab = true;

    protected $withoutParent = true;

    protected $belongsToModel = Product::class;

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
            'Nastavenie varianty' => Group::tab([
                'product_type' => 'type:imaginary|component:AddTypeFieldIntoRequest',
                Group::fields([
                    'name' => 'name:Názov varianty|required',
                    'image' => 'name:Obrázok varianty|image',
                ])->inline(),
                Group::fields([
                    'ean' => 'name:EAN varianty',
                    'code' => 'name:Kód varianty',
                ])->inline(),
            ])->grid(5)->icon('fa-pencil'),
            'Popis' => Group::tab([
                'description' => 'name:Popis varianty|type:editor|hidden',
            ])->icon('fa-file-text-o'),
            'Cena' => Group::tab([
                'Cena' => Group::fields([
                    'tax' => 'name:Sazba DPH|belongsTo:taxes,:name (:tax%)|defaultByOption:default,1|required|canAdd|hidden',
                    'price' => 'name:Cena bez DPH|type:decimal|default:0|component:PriceField|positivePriceIfRequired:variants|required_unless:product_type,'.implode(',', Store::orderableProductTypes()),
                ])->width(8),
                'Zľava' => Group::fields([
                    'discount_operator' => 'name:Typ zľavy|type:select|required_with:discount|hidden',
                    'discount' => 'name:Výška zľavy|type:decimal|hideFieldIfIn:discount_operator,NULL,default|required_if:discount_operator,'.implode(',', array_keys(operator_types())).'|hidden',
                ])->width(4),
            ])->icon('fa-money'),
            'Sklad' => Group::tab([
                'warehouse_quantity' => 'name:Počet na sklade|type:integer|default:0',
            ])->grid(7)->icon('fa-gear'),
            Group::tab( ProductsAttribute::class ),
        ];
    }

    protected $settings = [
        'increments' => false,
        'title.insert' => 'Nová varianta',
        'title.update' => 'Úprava varianty :name',
        'title.rows' => 'Zoznam variant',
        'grid' => [
            'default' =>'full',
            'disabled' => true,
        ],
        'columns.attributes' => [
            'name' => 'Atribúty',
            'before' => 'code',
        ],
        'buttons' => [
            'insert' => 'Nová varianta',
            'update' => 'Uložiť variantu',
            'create' => 'Pridať variantu',
        ],
        'autoreset' => false,
    ];

    protected $appends = [
        'initialPriceWithTax', 'initialPriceWithoutTax',
        'defaultPriceWithoutTax', 'defaultPriceWithTax',
        'priceWithTax', 'priceWithoutTax', 'clientPrice', 'stockText', 'hasStock',
    ];

    public function options()
    {
        return [
            'tax_id' => Store::getTaxes(),
            'discount_operator' => [ 'default' => 'Žiadna zľava' ] + operator_types(),
        ];
    }

    /*
     * This items will be selected frm db for cart items
     */
    protected $cartSelect = [
        'id', 'name', 'image', 'price', 'tax_id',
        'discount_operator', 'discount',
    ];
}