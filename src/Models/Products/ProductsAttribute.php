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

    protected $icon = 'fa-tint';

    protected $inTab = true;

    protected $withoutParent = true;

    protected $publishable = false;

    public function active()
    {
        return count($this->belongsToModel()) > 0;
    }

    public function belongsToModel()
    {
        return config('admineshop.attributes.eloquents', []);
    }

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
            'items' => 'name:Hodnota atribútu|belongsToMany:attributes_items,:name:unit|filterBy:attribute|canAdd|column_visible|required',
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
            'items' => $this->getVariantItemsOptions(),
        ];
    }

    protected $settings = [
        'title.insert' => 'Nový atribut',
        'title.update' => 'Upravujete atribút',
        'title.rows' => 'Zoznam atribútov kombinacie',
        'columns.id.hidden' => true,
        'grid.enabled' => false,
        'grid.default' => 'full',
        'buttons' => [
            'insert' => 'Nový atribút',
            'update' => 'Uložiť atribút',
            'create' => 'Priradiť atribút',
        ],
    ];

    protected $layouts = [
        'form-top' => 'setProductAttributes',
    ];

    public function getAttributesTextItems()
    {
        return $this->items;
    }
}