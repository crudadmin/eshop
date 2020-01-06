<?php

namespace AdminEshop\Models\Products;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use \AdminEshop\Models\Store\AttributesItem;

class ProductsVariantsAttribute extends AdminModel
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

    protected $group = 'store.products';

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
            'item' => 'name:Hodnota atribútu|belongsTo:attributes_items,name|component:AttributeItemValue|filterBy:attribute|canAdd|required',
        ];
    }

    /*
     * Push units into attribute item options from variant
     */
    private function getVariantItemsOptions()
    {
        $array = [];

        $items = AttributesItem::select(['id', 'attribute_id', 'name'])->whereHas('attribute')->with('attribute:id,unit')->get();

        foreach ($items as $item) {
            $array_item = $item->toArray();
            $array_item['name'] = $item->name.$item->attribute->unit;
            unset($array_item['attribute']);

            $array[$item->getKey()] = $array_item;
        }

        return $array;
    }

    public function options()
    {
        return [
            'item' => $this->getVariantItemsOptions(),
        ];
    }

    public function onUpdate($row)
    {
        if ( $this->variant )
            $row->variant->reloadSlug();
    }

    public function onCreate($row)
    {
        if ( $this->variant )
            $row->variant->reloadSlug();
    }

    protected $settings = [
        'title.insert' => 'Nový atribut k variante',
        'title.update' => 'Upravujete atribút',
        'title.rows' => 'Zoznam atribútov kombinacie',
        'columns.id.hidden' => true,
        'grid.enabled' => false,
        'grid.default' => 'full',
        'buttons' => [
            'update' => 'Uložiť atribút',
            'create' => 'Přidať atribút',
        ],
    ];
}