<?php

namespace AdminEshop\Contracts\Concerns;

use Admin;

trait HasStoreAttributes
{
    private $attributesScope;
    private $attributesFilter = [];

    public function setAttributesScope($attributesScope, $attributesFilter = [])
    {
        $this->attributesScope = $attributesScope;
        $this->attributesFilter = $attributesFilter;

        return $this;
    }

    public function getAttributes()
    {
        return $this->cache('store.attributes', function(){
            $model = Admin::getModel('Attribute');

            return $model
                        ->select($model->getAttributesColumns())
                        ->when(count($this->attributesFilter) == 0, function($query) {
                            $query->withItemsForProducts($this->attributesScope);
                        })
                        ->get()
                        ->each(function($attribute){
                            if ( $attribute->relationLoaded('items') == false ){
                                $attribute->loadItemsForProducts($this->attributesScope, $this->attributesFilter);
                            }

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
                        })
                        ->keyBy('id');
        });
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