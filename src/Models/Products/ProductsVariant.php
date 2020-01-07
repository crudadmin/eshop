<?php

namespace AdminEshop\Models\Products;

use AdminEshop\Eloquent\Concerns\HasProductAttributes;
use AdminEshop\Eloquent\Concerns\HasProductImage;
use AdminEshop\Eloquent\Concerns\HasWarehouse;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class ProductsVariant extends AdminModel
{
    use HasProductAttributes,
        HasWarehouse,
        HasProductImage;

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

    protected $publishable = false;

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
                Group::fields([
                    'name' => 'name:Názov varianty',
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
                    'tax' => 'name:Sazba DPH|belongsTo:taxes,:name (:tax%)|required|canAdd|hidden',
                    'price' => 'name:Cena bez DPH|type:decimal|default:0',
                ])->width(8),
                'Zľava' => Group::fields([
                    'discount_operator' => 'name:Typ zľavy|type:select|required_with:discount|hidden',
                    'discount' => 'name:Výška zľavy|type:decimal|required_with:discount_operator|hidden',
                ])->width(4),
            ])->icon('fa-money'),
            'Sklad' => Group::tab([
                'warehouse_quantity' => 'name:Počet na sklade|type:integer|default:0',
            ])->grid(7)->icon('fa-gear'),
            Group::tab( ProductsVariantsAttribute::class ),
        ];
    }

    protected $settings = [
        'title.insert' => 'Nová varianta',
        'title.update' => 'Úprava varianty',
        'title.rows' => 'Zoznam variant',
        // 'columns.id.hidden' => true,
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
}