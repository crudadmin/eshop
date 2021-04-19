<?php

namespace AdminEshop\Models\Attribute;

use Admin;
use AdminEshop\Contracts\Concerns\HasUnit;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class Attribute extends AdminModel
{
    use HasUnit;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-11 17:47:15';

    /*
     * Template name
     */
    protected $name = 'Atribúty';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'products';

    protected $reversed = true;

    protected $sluggable = 'name';

    protected $options = [
        'sortby' => [
            'asc' => 'Zostupne',
            'desc' => 'Vzostupne',
            'own' => 'Vlastné radenie',
        ],
    ];

    public function settings()
    {
        return [
            'title.insert' => 'Nový atribút',
            'title.update' => ':name',
            'columns.id.hidden' => env('APP_DEBUG') == false,
        ];
    }

    public function active()
    {
        return count(config('admineshop.attributes.eloquents', [])) > 0;
    }

    public function reserved()
    {
        return array_filter([
            env('ATTR_COLOR_ID'),
        ]);
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
            'name' => 'name:Názov atribútu|required'.(Store::isEnabledLocalization() ? '|locale' : ''),
            'unit' => 'name:Merná jednotka|belongsTo:attributes_units,:name (:unit)|canAdd',
            'title' => 'name:Popis|'.(Store::isEnabledLocalization() ? '|locale' : ''),
            'sortby' => 'name:Zoradiť podľa|type:select|required|default:asc',
        ];
    }

    public function mutateFields($fields)
    {
        if ( config('admineshop.attributes.filtrable', true) === true ) {
            $fields->push([
                'filtrable' => 'name:Filtrovať podľa atribútu|type:checkbox|default:0',
            ]);
        }
    }

    public function scopeWithItemsForProducts($query, $productsQuery)
    {
        $attributes = $query->select(
            $this->getAttributesColumns()
        )->with([
            'items' => function($query) use ($productsQuery) {
                $query->select(
                    Admin::getModel('AttributesItem')->getAttributesItemsColumns()
                )->whereHas('productsAttributes', function($query) use ($productsQuery) {
                    //Get attribute items from all products
                    if ( (new Product)->hasAttributesEnabled() ) {
                        $query->whereHas('products', $productsQuery);
                    }

                    //Get attribute items also from all variants
                    if ( (new ProductsVariant)->hasAttributesEnabled() ) {
                        $query->orWhereHas('variants.product', $productsQuery);
                    }
                });
            }
        ]);
    }

    /**
     * This columns will be loaded into list of attributes in category response
     *
     * @return  array
     */
    public function getAttributesColumns()
    {
        return array_filter([
            'id', 'name', 'unit_id', 'slug',
            config('admineshop.attributes.filtrable', true) ? 'filtrable' : null,
        ]);
    }
}