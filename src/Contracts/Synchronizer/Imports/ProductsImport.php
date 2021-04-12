<?php

namespace AdminEshop\Contracts\Synchronizer\Imports;

use AdminEshop\Contracts\Synchronizer\Synchronizer;
use AdminEshop\Contracts\Synchronizer\SynchronizerInterface;
use Admin;
use Store;
use DB;

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

    public function getAttributeIdentifier()
    {
        return 'code';
    }

    public function getAttributesItemIdentifier()
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

    public function setFinalProductsAttributeItemsHashAttribute($value, &$row, $dbRow)
    {
        $itemsIds = array_map(function($item) use ($row, $dbRow) {
            $identifier = $item[$this->getAttributesItemIdentifier()];

            return $this->getExistingRows('attributes_items')[$identifier];
        }, $row['$items']);

        $existingIds = DB::table('attributes_item_products_attribute_items')
                            ->selectRaw('attributes_item_id as item_id')
                            ->where('products_attribute_id', $dbRow->id)
                            ->get()->pluck('item_id')->toArray();

        //Insert missing ids
        $toInsert = array_diff($itemsIds, $existingIds);
        if ( count($toInsert) ) {
            DB::table('attributes_item_products_attribute_items')->insert(array_map(function($id) use($dbRow){
                return [
                    'products_attribute_id' => $dbRow->id,
                    'attributes_item_id' => $id,
                ];
            }, $toInsert));
        }

        //Remove uneccessary ids
        $toRemove = array_diff($existingIds, $itemsIds);
        if ( count($toRemove) ) {
            DB::table('attributes_item_products_attribute_items')
                ->where('products_attribute_id', $dbRow->id)
                ->whereIn('attributes_item_id', $toRemove)
                ->delete();
        }

        return $value;
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