<?php

namespace AdminEshop\Models\Store;

use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class Attribute extends AdminModel
{
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

    protected $settings = [
        'title.insert' => 'Nový atribút',
        'title.update' => ':name',
        'columns.id.hidden' => true,
    ];

    public function active()
    {
        return count(config('admineshop.attributes.eloquents', [])) > 0;
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
            'name' => 'name:Názov atribútu|required',
            'unit' => 'name:Merná jednotka',
            'title' => 'name:Popis',
            'sortby' => 'name:Zoradiť podľa|type:select|required|default:asc',
        ];
    }

    public function scopeWithItemsForProducts($query, $productsQuery)
    {
        $attributes = $query->select([
            'id', 'name', 'unit', 'slug'
        ])->with([
            'items' => function($query) use ($productsQuery) {
                $query->select([
                    'attributes_items.id',
                    'attributes_items.attribute_id',
                    'attributes_items.name',
                    'attributes_items.slug',
                ])->whereHas('productsAttributes', function($query) use ($productsQuery) {
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

    public function getAttributesSelect()
    {
        return [
            'attributes.name',
            'attributes.unit',
        ];
    }
}