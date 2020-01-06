<?php

namespace AdminEshop\Models\Products;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class ProductsCategory extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 17:43:17';

    /*
     * Template name
     */
    protected $name = 'Kategórie';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'store.products';

    protected $reversed = true;

    protected $sluggable = 'name';

    protected $belongsToModel = ProductsCategory::class;

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    protected $fields = [
        'name' => 'name:Názov Kategórie|index|required',
    ];

    protected $settings = [
        'title.insert' => 'Nová Kategórie',
        'title.update' => ':name',
        'buttons.insert' => 'nová Kategórie',
        'columns.id.hidden' => true,
    ];
}