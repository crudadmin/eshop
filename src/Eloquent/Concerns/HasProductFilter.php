<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;
use Store;

trait HasProductFilter
{
    public function scopeFilterCategory($query, $category)
    {
        $query->whereHas('categories', function($query) use ($category) {
            $query->where('categories.id', $category->getKey())->withoutGlobalScope('order');
        });
    }

    public function scopeFilterAttributeItems($query, int $attributeId, array $itemIds)
    {
        $query->whereHas('attributesItems', function($query) use ($attributeId, $itemIds) {
            $query
                ->where('products_attributes.attribute_id', $attributeId)
                ->whereIn('attributes_item_products_attribute_items.attributes_item_id', $itemIds)
                ->leftJoin('attributes_items', 'attributes_items.id', '=', 'attributes_item_products_attribute_items.attributes_item_id');
        });
    }

    public function scopeApplyQueryFilter($query, $params, $filterVariants = false)
    {
        $params = is_array($params) ? $params : [];

        $filter = [];

        //If no filter params are present
        if ( count($params) > 0 ){
            $existingAttributes = Store::getExistingAttributesFromFilter($params);

            foreach ($params as $key => $value) {
                //Denny non attributes queries
                if ( array_key_exists($key, $existingAttributes) ){
                    $attributeId = $existingAttributes[$key]['id'];

                    $filter[$attributeId] = explode(',', $value);
                }
            }
        }

        $query->where(function($query) use ($filter, $filterVariants) {
            if ( $filterVariants === false ) {
                $query
                    //Filter by basic product type
                    ->where(function($query) use ($filter) {
                        $query->nonVariantProducts();

                        foreach ($filter as $attributeId => $itemIds) {
                            $query->filterAttributeItems($attributeId, $itemIds);
                        }
                    })
                    //Filter product by variant attributes
                    ->orWhere(function($query) use ($filter) {
                        $query
                            ->variantProducts()
                            ->whereHas('variants', function($query) use ($filter) {
                                foreach ($filter as $attributeId => $itemIds) {
                                    $query->filterAttributeItems($attributeId, $itemIds)->withoutGlobalScope('order');
                                }
                            });
                    });
            }

            //If actual object is already variant, we need filter only attributes
            else {
                $query->withoutGlobalScope('order');

                foreach ($filter as $attributeId => $itemIds) {
                    $query->filterAttributeItems($attributeId, $itemIds);
                }
            }
        });
    }
}