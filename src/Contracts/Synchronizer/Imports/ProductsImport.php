<?php

namespace AdminEshop\Contracts\Synchronizer\Imports;

use Admin;
use AdminEshop\Contracts\Synchronizer\Synchronizer;
use AdminEshop\Contracts\Synchronizer\SynchronizerInterface;
use AdminEshop\Models\Products\Pivot\ProductsCategoriesPivot;
use DB;
use Store;

class ProductsImport extends Synchronizer implements SynchronizerInterface
{
    public function getProductIdentifier()
    {
        return 'code';
    }

    public function getProductsVariantIdentifier()
    {
        return 'code';
    }

    public function getProductsGalleryIdentifier()
    {
        return ['product_id', 'products_variant_id', 'code'];
    }

    public function getAttributeIdentifier()
    {
        return 'code';
    }

    public function getAttributesItemIdentifier()
    {
        return 'code';
    }

    public function getCategoryIdentifier()
    {
        return 'code';
    }

    public function getProductsAttributeIdentifier()
    {
        return ['product_id', 'products_variant_id', 'attribute_id'];
    }

    public function handle(array $rows = null)
    {
        $this->synchronize(
            Admin::getModel('Product'),
            $this->getProductIdentifier(),
            $rows
        );

        $this->synchronize(
            Admin::getModel('ProductsVariant'),
            $this->getProductsVariantIdentifier(),
            $this->getPreparedVariants($rows)
        );

        $this->synchronize(
            new ProductsCategoriesPivot,
            ['product_id', 'category_id'],
            $this->getPreparedProductsCategories($rows)
        );

        $this->synchronize(
            Admin::getModel('ProductsGallery'),
            $this->getProductsGalleryIdentifier(),
            $this->getPreparedGallery($rows)
        );

        $this->synchronize(
            Admin::getModel('Attribute'),
            $this->getAttributeIdentifier(),
            $preparedAttributes = $this->getPreparedAttributes($rows)
        );

        $this->synchronize(
            Admin::getModel('AttributesItem'),
            $this->getAttributesItemIdentifier(),
            $this->getPreparedAttributesItems($preparedAttributes)
        );

        $this->synchronize(
            Admin::getModel('ProductsAttribute'),
            $this->getProductsAttributeIdentifier(),
            $this->getPreparedProductsAttribute($rows)
        );
    }

    public function getCategories()
    {
        return $this->cache('import.categories', function(){
            return Admin::getModel('Category')->select('id', 'category_id', $this->getCategoryIdentifier())->get();
        });
    }

    private function getPreparedVariants($rows)
    {
        $variants = [];

        foreach ($rows as $product) {
            foreach ($product['$variants'] ?? [] as $variant) {
                $variants[] = $variant + [
                    'product_id' => $this->getExistingRows('products')[
                        $product[$this->getProductIdentifier()]
                    ],
                ];
            }
        }

        return $variants;
    }

    private function getPreparedProductsCategories($rows)
    {
        $categories = [];

        $dbCategories = $this->getCategories();
        $categoriesTree = $dbCategories->keyBy('id')->map(function($item) use ($dbCategories) {
            return $item->getCategoryTreeIds($item, $dbCategories);
        });

        foreach ($rows as $row) {
            if ( !isset($row['$categories']) ){
                continue;
            }

            //Add all parent categories with added category
            $toAdd = [];
            foreach ($row['$categories'] as $id) {
                $toAdd = array_merge($categoriesTree[$id]);
            }

            //Merge parent categories with setted categories
            $allCategoriesTree = array_unique(array_merge($toAdd, $row['$categories']));
            asort($allCategoriesTree);
            foreach ($allCategoriesTree as $id) {
                $categories[] = [
                    'product_id' => $this->getExistingRows('products')[$row[$this->getProductIdentifier()]],
                    'category_id' => $id,
                ];
            }
        }

        return $categories;
    }

    private function getPreparedGallery($rows, $gallery = [], $relationTable = 'products', $relationName = 'product_id', $relationIdentifier = 'getProductIdentifier')
    {
        foreach ($rows as $row) {
            if ( isset($row['$variants']) && count($row['$variants']) ) {
                $gallery = $this->getPreparedGallery(
                    $row['$variants'],
                    $gallery,
                    'products_variants',
                    'products_variant_id',
                    'getProductsVariantIdentifier'
                );
            }

            foreach ($row['$gallery'] ?? [] as $image) {
                $image[$relationName] = $this->getExistingRows($relationTable)[
                    $row[$this->{$relationIdentifier}()]
                ];

                $gallery[] = $image;
            }
        }

        return $gallery;
    }

    private function getPreparedAttributes($rows, $attributes = [])
    {
        foreach ($rows as $row) {
            if ( isset($row['$variants']) && count($row['$variants']) ) {
                $attributes = $this->getPreparedAttributes($row['$variants'], $attributes);
            }

            if ( !isset($row['$attributes']) ){
                continue;
            }

            foreach ($row['$attributes'] as $attribute) {
                $attrId = $attribute[$this->getAttributeIdentifier()];

                //We want merge items if attribute exists already
                if ( array_key_exists($attrId, $attributes) ) {
                    $attributes[$attrId]['$items'] = collect(array_merge($attributes[$attrId]['$items'], $attribute['$items']))
                                                        ->unique($this->getAttributesItemIdentifier())
                                                        ->toArray();

                } else {
                    $attributes[$attrId] = $attribute;
                }
            }
        }

        return $attributes;
    }

    private function getPreparedAttributesItems($attributes)
    {
        $items = [];

        foreach ($attributes as $attribute) {
            foreach ($attribute['$items'] ?? [] as $item) {
                $item['attribute_id'] = $this->getExistingRows('attributes')[$attribute[$this->getAttributeIdentifier()]];

                $items[] = $item;
            }
        }

        return $items;
    }

    private function getPreparedProductsAttribute($rows, $productsAttribute = [], $relationTable = 'products', $relationName = 'product_id', $relationIdentifier = 'getProductIdentifier')
    {
        foreach ($rows as $row) {
            foreach ($row['$attributes'] ?? [] as $attribute) {
                $itemString = implode(';', array_map(function($item){
                    return $item[$this->getAttributesItemIdentifier()];
                }, $attribute['$items']));
                $itemHash = crc32($itemString);

                $item = [
                    $relationName => $this->getExistingRows($relationTable)[$row[$this->{$relationIdentifier}()]],
                    'attribute_id' => $this->getExistingRows('attributes')[$attribute[$this->getAttributeIdentifier()]],
                    'items_hash' => $itemHash,
                    '$items' => $attribute['$items'],
                ];

                $productsAttribute[] = $item;
            }

            if ( isset($row['$variants']) && count($row['$variants']) ){
                $productsAttribute = $this->getPreparedProductsAttribute(
                    $row['$variants'],
                    $productsAttribute,
                    'products_variants',
                    'products_variant_id',
                    'getProductsVariantIdentifier'
                );
            }
        }

        return $productsAttribute;
    }

    public function setProductsVatNumberAttribute($value, &$row)
    {
        $row['vat_id'] = $this->getVatIdByValue($value);
    }

    public function setProductsVariantVatNumberAttribute($value, &$row)
    {
        $row['vat_id'] = $this->getVatIdByValue($value);
    }

    public function setAfterProductsAttributeItemsHashAttribute($value, &$row, $dbRow)
    {
        $productsAttributeId = $dbRow->id;

        //We need remove all previous attributes, and insert them again for correct order
        DB::table('attributes_item_products_attribute_items')
            ->where('products_attribute_id', $productsAttributeId)
            ->delete();

        $toInsert = array_map(function($item) use ($row) {
            $identifier = $item[$this->getAttributesItemIdentifier()];

            return $this->getExistingRows('attributes_items')[$identifier];
        }, $row['$items']);

        //Insert missing ids
        if ( count($toInsert) ) {
            DB::table('attributes_item_products_attribute_items')->insert(array_map(function($id) use($productsAttributeId){
                return [
                    'products_attribute_id' => $productsAttributeId,
                    'attributes_item_id' => $id,
                ];
            }, $toInsert));
        }
    }

    private function getVatIdByValue($value)
    {
        return $this->cache('vat.'.$value, function() use ($value){
            if ( $vat = Store::getVats()->where('vat', $value)->first() ){
                return $vat->getKey();
            }

            $vat = Admin::getModel('Vat')->create([
                'name' => $value.'%',
                'vat' => $value,
            ]);

            return $vat->getKey();
        });
    }
}