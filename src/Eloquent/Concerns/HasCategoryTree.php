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
            $categories = Admin::getModel('Category')
                            ->select(['id', 'name', 'category_id'])
                            ->withUnpublished()->get()->keyBy('id');

            $tree = [];

            foreach ($categories as $category) {
                $categoryTree = $this->getCategoryTreeName($category, $categories);

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

    private function getCategoryTreeName($category, $categories, $tree = [])
    {
        $tree = array_merge([ $category->getKey() ], $tree);

        if (
            $category->category_id
            && $parentCategory = $categories->where('id', $category->category_id)->first()
        ){
            $tree = $this->getCategoryTreeName($parentCategory, $categories, $tree);
        }

        return $tree;
    }
}