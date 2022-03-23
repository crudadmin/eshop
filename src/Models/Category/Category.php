<?php

namespace AdminEshop\Models\Category;

use AdminEshop\Eloquent\Concerns\HasCategoryTree;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class Category extends AdminModel
{
    use HasCategoryTree;

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

    protected $withRecursiveRows = true;

    protected $layouts = [
        'table-before' => 'CategoriesTree',
    ];

    public function belongsToModel()
    {
        if ( config('admineshop.categories.max_level', 1) > 1 ) {
            return get_class($this);
        }
    }

    public function settings()
    {
        return [
            'title.update' => ':name',
            'recursivity.name' => 'Podkategórie',
            'recursivity.max_depth' => config('admineshop.categories.max_level'),
            'pagination.enabled' => false,
            'table.enabled' => false,
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
            'category' => 'name:Patri do kategórie|belongsTo:categories,name|title:Kategória je priradená do tejto nadradenej kategórie',
        ];
    }

    public function options()
    {
        return [
            'category_id' => $this->getCategoriesOptions(),
        ];
    }

    public function categories()
    {
        return $this->hasMany(get_class($this), 'category_id');
    }

    public function scopeAdminRows($query)
    {
        $query->withCount('products');
    }

    public function setAdminRowsAttributes($attributes)
    {
        $attributes['products_count'] = $this->products_count;

        return $attributes;
    }
}