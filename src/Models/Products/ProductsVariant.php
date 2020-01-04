<?php

namespace AdminEshop\Models\Products;

use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Fields\Group;

class ProductsVariant extends AdminModel
{
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

    protected $group = 'store.products';

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
            'Nastavení varianty' => Group::tab([
                'ean' => 'name:EAN varianty',
                'code' => 'name:Kód varianty',
                'slug' => 'name:Url|invisible|index',
                'image' => 'name:Obrázok varianty|hidden|image',
            ])->grid(5)->icon('fa-pencil'),
            Group::tab( ProductsVariantsAttribute::class ),
            'Sklad' => Group::tab([
                'warehouse_quantity' => 'name:Počet na sklade|type:integer|default:0',
            ])->grid(7)->icon('fa-gear'),
            'Cena' => Group::tab([
                'Cena' => Group::full([
                    Group::half([
                        'price_operator' => 'name:Spôsob upravy ceny|type:select|default:default',
                    ]),
                    Group::half([
                        'price_value' => 'name:Upraviť cenu o|component:ProductVariantPrice|type:decimal',
                    ]),

                    'price' => 'name:Základna cena bez DPH|invisible|type:decimal|default:0',
                    'pricings' => 'name:Cenníky|component:productPrice|type:json|hidden',
                ]),
                'Sleva' => Group::full([
                    'discount_operator' => 'name:Typ zľavy|type:select|required_with:discount|hidden',
                    'discount' => 'name:Výška zľavy|type:decimal|hidden',
                ]),
            ])->add('hidden')->icon('fa-money'),
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
            'update' => 'Uložiť variantu',
            'create' => 'Pridať variantu',
        ],
        'autoreset' => false,
    ];
}