<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;
use Store;
use Admin;

trait HasProductFilter
{
    public function scopeFilterCategory($query, $category)
    {
        $query->whereHas('categories', function($query) use ($category) {
            $query->where('categories.id', $category->getKey())->withoutGlobalScope('order');
        });
    }

    public function scopeFilterAttributeBySlug($query, string $attributeSlug, array $itemIds)
    {
        $query->whereHas('attributes', function($query) use ($attributeSlug, $itemIds) {
            $query
                ->withoutGlobalScope('order')
                ->whereHas('attribute', function($query) use ($attributeSlug) {
                    $query->where('slug', $attributeSlug)->withoutGlobalScope('order');
                })->whereHas('items', function($query) use ($itemIds) {
                    $query->whereIn('attributes_items.id', $itemIds)->withoutGlobalScope('order');
                });
        });
    }

    public function scopeApplyQueryFilter($query, $params)
    {
        $params = is_array($params) ? $params : [];

        //If no filter params are present
        if ( count($params) == 0 ){
            return;
        }

        $existingAttributes = Store::cache('existing_attributes', function() use ($params) {
            return Admin::getModel('Attribute')->whereIn('slug', array_keys($params))->pluck('slug')->toArray();
        });

        foreach ($params as $key => $value) {
            $attributeSlug = $key;
            $itemIds = explode(',', $value);

            //Denny non attributes queries
            if ( !in_array($key, $existingAttributes) ){
                continue;
            }

            $query->where(function($query) use ($attributeSlug, $itemIds) {
                $model = $query->getModel();

                if ( $model instanceof Product ) {
                    $query
                        //Filter by basic product type
                        ->where(function($query) use ($attributeSlug, $itemIds) {
                            $query
                                ->nonVariantProducts()
                                ->filterAttributeBySlug($attributeSlug, $itemIds);
                        })

                        //Filter product by variant attributes
                        ->orWhere(function($query) use ($attributeSlug, $itemIds) {
                            $query
                                ->variantProducts()
                                ->whereHas('variants', function($query) use ($attributeSlug, $itemIds) {
                                    $query->filterAttributeBySlug($attributeSlug, $itemIds)->withoutGlobalScope('order');
                                });
                        });
                }

                //If actual object is already variant, we need filter only attributes
                else if ( $model instanceof ProductsVariant ) {
                    $query->filterAttributeBySlug($attributeSlug, $itemIds)->withoutGlobalScope('order');
                }
            });
        }
    }
}