<?php

namespace AdminEshop\Contracts\Concerns;

use Admin;

trait HasStoreAttributes
{
    private $attributesOptions;

    public function setAttributesScope($options)
    {
        $this->attributesOptions = $options;

        return $this;
    }

    public function getAttributes()
    {
        $attributes = $this->cache('store.attributes', function(){
            $model = Admin::getModel('Attribute');

             return $model
                        ->select($model->getAttributesColumns())
                        ->get()
                        ->keyBy('id');
        });

        return $this->addFiltratedItemsIntoAttributes($attributes);
    }

    private function addFiltratedItemsIntoAttributes($attributes)
    {
        return $this->cache('store.attributes.items', function() use ($attributes) {
            $options = $this->attributesOptions;

            $filter = Admin::getModel('Product')->getFilterFromQuery($options['filter'] ?? []);

            $filtrableAttributes = $attributes->where('filtrable', true)->pluck('id')->toArray();

            $hasFilter = count($filter) >= 1;

            $modelItems = Admin::getModel('AttributesItem');

            if ( $hasFilter ){
                $items = $modelItems
                            ->withListingItems()
                            ->whereIn('attribute_id', array_values(array_diff($filtrableAttributes, array_keys($filter))))
                            ->where(function($query) use ($filter, $options) {
                                foreach ($filter as $attributeId => $itemIds) {
                                    $query->orWhereHas('products', function($query) use ($filter, $attributeId, $options) {
                                        foreach ($filter as $subAttributeId => $itemIds) {
                                            $query->filterAttributeItems($itemIds);
                                        }

                                        $this->filterByItemsProduct($query, $options);
                                    });

                                }
                            })->get();

                $itemsFilrated = $modelItems
                    ->withListingItems()
                    ->whereIn('attribute_id', array_keys($filter))
                    //Only filtrated fields
                    ->where(function($query) use ($filter, $options) {
                        foreach ($filter as $attributeId => $itemIds) {
                            $query->orWhere(function($query) use ($filter, $attributeId, $options) {
                                $query
                                    ->where('attribute_id', $attributeId)
                                    ->whereHas('products', function($query) use ($filter, $attributeId, $options) {
                                        unset($filter[$attributeId]);

                                        foreach ($filter as $subAttributeId => $itemIds) {
                                            $query->filterAttributeItems($itemIds);
                                        }

                                        $this->filterByItemsProduct($query, $options);
                                    });
                            });
                        }
                    })
                    ->get();

                $items = $items->merge($itemsFilrated);
            } else {
                $items = $modelItems
                    ->whereIn('attribute_id', $filtrableAttributes)
                    ->withListingItems()
                    ->whereHas('products', function($query) use ($options) {
                        $this->filterByItemsProduct($query, $options);
                    })
                    ->get();
            }

            $items = $items->groupBy('attribute_id');

            return $attributes->each(function($attribute) use ($items) {
                if ( $attribute->relationLoaded('items') == false ){
                    $attribute->setRelation('items', $items[$attribute->getKey()] ?? collect());
                }

                $this->sortAttributesItems($attribute);
            });
        });
    }

    private function filterByItemsProduct($query, $options)
    {
        $query->setFilterOptions(array_merge($options ?: [], [
            '$ignore.filter.attributes' => true,
        ]));

        $query->applyQueryFilter();
    }

    private function sortAttributesItems($attribute)
    {
          //Skip reordeing, already ordered from database
        if ( $attribute->sortby == 'order' ){
            return;
        }

        $isDescSort = in_array($attribute->sortby, ['desc']);

        $itemsSorted = $attribute->items->{ $isDescSort ? 'sortByDesc' : 'sortBy' }(function($a, $b) use ($attribute) {
            if ( in_array($attribute->sortby, ['asc', 'desc']) ){
                return $a->name;
            }

            return $a->getKey();
        })->values();

        $attribute->setRelation('items', $itemsSorted);
    }

    public function getExistingAttributesFromFilter($filter)
    {
        $filter = array_wrap($filter);

        return $this->cache('existing_attributes.'.implode(',', array_keys($filter)), function() use ($filter) {
            $attributeIdentifiers = array_keys($filter);

            //Check if given attribute keys are string, if yes, then search attribute by slug and not by ID
            $isSlugIdentifier = count(array_filter($attributeIdentifiers, function($identifier){
                return is_string($identifier);
            })) == count($attributeIdentifiers);

            $keyIdentifier = $isSlugIdentifier ? 'slug' : 'id';

            return Admin::getModel('Attribute')->select('id', 'name', 'slug')->get()->whereIn($keyIdentifier, $attributeIdentifiers)->keyBy($keyIdentifier)->toArray();
        });
    }

    public function getFilterAttributes()
    {
        return $this->getAttributes()->where('filtrable', true)->values();
    }

    private function getAttributesItems()
    {
        return $this->cache('store.attributes_items', function(){
            $items = [];

            foreach ($this->getAttributes() as $attribute) {
                foreach ($attribute->items as $item) {
                    $items[$item->getKey()] = $item;
                }
            }

            return $items;
        });
    }

    public function getAttribute($id)
    {
        return $this->getAttributes()[$id] ?? null;
    }

    public function getAttributeItem($id)
    {
        $items = $this->getAttributesItems();

        return $items[$id] ?? null;
    }
}