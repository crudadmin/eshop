<?php

namespace AdminEshop\Models\Category;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class Category extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2021-03-28 14:15:04';

    /*
     * Template name
     */
    protected $name = 'Kategórie produktov';

    /*
     * Template title
     */
    protected $title = '';

    protected $group = 'products';

    protected $reversed = true;

    protected $seo = true;

    protected $sluggable = 'name';

    protected $belongsToModel = Category::class;

    public function settings()
    {
        return [
            'title.update' => ':name',
            'recursivity.name' => 'Podkategórie',
            'recursivity.max_depth' => config('admineshop.categories.max_level'),
        ];
    }

    /*
     * Automatic form and database generator by fields list
     * :name - field name
     * :type - field type (string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio)
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'name' => 'name:Názov kategórie|required|max:90'.(Store::isEnabledLocalization() ? '|locale' : ''),
            'code' => 'name:Kód kategórie|index|max:30',
        ];
    }
}