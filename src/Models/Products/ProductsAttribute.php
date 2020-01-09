<?php

namespace AdminEshop\Models\Products;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use \AdminEshop\Models\Store\AttributesItem;

class ProductsAttribute extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-12 17:36:12';

    /*
     * Template name
     */
    protected $name = 'Atribúty';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $inTab = true;
    protected $withoutParent = true;

    protected $belongsToModel = ProductsVariant::class;

    protected $publishable = false;

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
            'attribute' => 'name:Atribút|belongsTo:attributes,name|canAdd|required',
            'item' => 'name:Hodnota atribútu|belongsTo:attributes_items,:name:unit|filterBy:attribute|canAdd|required',
        ];
    }

    /*
     * Push units into attribute item options from variant
     */
    private function getVariantItemsOptions()
    {
        return AttributesItem::select(['attributes_items.id', 'attributes_items.attribute_id', 'attributes_items.name', 'attributes.unit'])
                ->leftJoin('attributes', 'attributes.id', '=', 'attributes_items.attribute_id')
                ->get();
    }

    public function options()
    {
        return [
            'item' => $this->getVariantItemsOptions(),
        ];
    }

    protected $settings = [
        'title.insert' => 'Nový atribut k variante',
        'title.update' => 'Upravujete atribút',
        'title.rows' => 'Zoznam atribútov kombinacie',
        'columns.id.hidden' => true,
        'grid.enabled' => false,
        'grid.default' => 'full',
        'buttons' => [
            'insert' => 'Nový atribút',
            'update' => 'Uložiť atribút',
            'create' => 'Pridať atribút',
        ],
    ];

    protected $layouts = [
        'form-top' => 'setProductAttributes',
    ];
}