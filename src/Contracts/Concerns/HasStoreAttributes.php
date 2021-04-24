<?php

namespace AdminEshop\Contracts\Concerns;

use Admin;

trait HasStoreAttributes
{
    private $attributesScope;

    public function setAttributesScope($attributesScope)
    {
        $this->attributesScope = $attributesScope;

        return $this;
    }

    public function getAttributes()
    {
        return $this->cache('store.attributes', function(){
            $model = Admin::getModel('Attribute');

            return $model
                        ->select($model->getAttributesColumns())
                        ->withItemsForProducts($this->attributesScope)
                        ->get()
                        ->each(function($attribute){
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

    public function getFilterAttributes()
    {
        return $this->getAttributes()->where('filtrable', true);
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