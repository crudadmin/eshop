<?php

namespace AdminEshop\Models\Products;

use Gogol\Admin\Models\Model as AdminModel;
use Illuminate\Support\Collection;
use AdminEshop\Traits\ProductTrait;
use AdminEshop\Models\Store\Manufacturer;
use Gogol\Admin\Fields\Group;
use Gogol\Admin\Helpers\File as AdminFile;
use Basket;
use Store;

class Product extends AdminModel
{
    use ProductTrait;

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

    protected $group = 'store.products';

    protected $sluggable = 'name';

    public $attribute = null;
    public $variant = null;

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
                'name' => 'name:Názov produktu|index|limit:30|required',
                'image' => 'name:Obrázok|type:file|image',
                'categories' => 'name:Kategórie produktu|belongsToMany:products_categories,name|canAdd',
                'ean' => 'name:EAN|hidden',
                'code' => 'name:Kód produktu',
            ])->icon('fa-pencil'),
            'Popis' => Group::tab([
                'description' => 'name:Popis produktu|type:text|hidden',
            ])->icon('fa-file-text-o'),
            'Ostatné nastavenia' => Group::tab([
                'created_at' => 'name:Vytvorené dňa|default:CURRENT_TIMESTAMP|type:datetime|disabled',
                'published_at' => 'name:Publikovať od|default:CURRENT_TIMESTAMP|type:datetime',
            ])->icon('fa-gear'),
            'Sklad' => Group::tab([
                'warehouse_quantity' => 'name:Sklad|component:productQuantity|type:integer|default:0',
                'warehouse_type' => 'name:Možnosti skladu|default:show|type:select|index',
            ])->icon('fa-bars')->add('hidden'),
            'Cena' => Group::tab([
                'Cena' => Group::fields([
                    'tax' => 'name:Sazba DPH|belongsTo:taxes,:name (:tax%)|required|canAdd|hidden',
                    'price' => 'name:Cena bez DPH|invisible|type:decimal|default:0',
                    'pricings' => 'name:Ceníky|component:productPrice|type:json|hidden',
                    'price_minimum' => 'name:Minimálna cena produktu|hidden|type:decimal|title:Najnižšia cena produktu (bez DPH) po odčítani všetkých zliav. Ak je cena po výpočte menšia ako minimálna cena produktu, bude platit minimálna cena.',
                ])->width(8),
                'Zľava' => Group::fields([
                    'discount_operator' => 'name:Typ zľavy|type:select|required_with:discount|hidden',
                    'discount' => 'name:Výška zľavy|type:decimal|required_with:discount_operator|hidden',
                ])->width(4),
            ])->icon('fa-money'),
        ];
    }

    public function options()
    {
        return [
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
}