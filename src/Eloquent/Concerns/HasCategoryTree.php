<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;

/**
 * @see  AdminEshop\Eloquent\Concerns\DiscountHelper for implementing this methods
 * You only need create buildCartItem, if your model is not assigned to cart
 */
trait HasCategoryTree
{
    protected function getCategoriesOptions()
    {
        return Admin::cache('store.categories.tree', function(){
            $model = Admin::getModel('Category');
            $categories = $model
                            ->select(array_filter([
                                'id',
                                'name',
                                $model->getProperty('belongsToModel') ? 'category_id' : null
                            ]))
                            ->withUnpublished()->get()->keyBy('id');

            $tree = [];

            foreach ($categories as $category) {
                $categoryTree = $this->getCategoryTreeIds($category, $categories);

                $tree[] = [
                    'id' => $category->getKey(),
                    'name' => implode(' >> ', array_map(function($id) use ($categories){
                        return $categories[$id]->name;
                    }, $categoryTree)),
                    'tree' => $categoryTree,
                ];
            }

            return $tree;
        });
    }

    public function getCategoryTreeIds($category, $categories, $tree = [])
    {
        $tree = array_merge([ $category->getKey() ], $tree);

        if (
            $category->category_id
            && $parentCategory = $categories->where('id', $category->category_id)->first()
        ){
            $tree = $this->getCategoryTreeIds($parentCategory, $categories, $tree);
        }

        return $tree;
    }
}