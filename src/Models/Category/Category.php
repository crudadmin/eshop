<?php

namespace AdminEshop\Models\Category;

use AdminEshop\Eloquent\Concerns\HasCategoryTree;
use AdminEshop\Models\Products\Pivot\ProductsCategoriesPivot;
use AdminEshop\Models\Products\Product;
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
            'buttons.create.enabled' => false,
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
            Group::inline([
                'name' => 'name:Názov kategórie|required|max:90'.(Store::isEnabledLocalization() ? '|locale' : ''),
                'category' => 'name:Patri do kategórie|belongsTo:categories,name|title:Kategória je priradená do tejto nadradenej kategórie|readonly',
            ]),
            'code' => 'name:Kód kategórie|index|max:30',
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

    private function getParentCategoryIds($categoryId)
    {
        $parentCategoryIds = [];

        while ($actualCategory = $this->newQuery()->select('category_id')->where('id', $categoryId)->first()) {
            $parentCategoryIds[] = $categoryId;

            if ( !($categoryId = isset($actualCategory) ? $actualCategory->category_id : $categoryId) ){
                break;
            }
        }

        return array_reverse($parentCategoryIds);
    }

    public function onRecursiveDragAndDrop($movedCategoryId, $key, $toCategoryId)
    {
        $removeParentIds = $this->getParentCategoryIds($movedCategoryId);
        $newParentIds = collect(array_merge($this->getParentCategoryIds($toCategoryId), [ $movedCategoryId ]));

        $productsToRefreshIds = ProductsCategoriesPivot::where('category_id', $movedCategoryId)->select('product_id')->pluck('product_id');

        //Delete all assigned categories in this product
        ProductsCategoriesPivot::whereIn('product_id', $productsToRefreshIds->toArray())->whereIn('category_id', $removeParentIds)->delete();

        $productsToRefreshIds->chunk(100)->each(function($productIds) use ($newParentIds) {
            $productIds->each(function($productId) use ($newParentIds) {
                $toInsert = $newParentIds->map(function($categoryId) use ($productId) {
                    return [
                        'product_id' => $productId,
                        'category_id' => $categoryId,
                    ];
                });

                ProductsCategoriesPivot::insert($toInsert->toArray());
            });
        });
    }

    public function setBootstrapResponse()
    {
        return $this;
    }

    public function setListingResponse()
    {
        return $this;
    }
}