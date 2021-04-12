<?php

namespace AdminEshop\Contracts\Synchronizer\Imports;

use AdminEshop\Contracts\Synchronizer\Synchronizer;
use AdminEshop\Contracts\Synchronizer\SynchronizerInterface;
use Admin;
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
            if ( !isset($row['$attributes']) ){
                continue;
            }

            foreach ($row['$attributes'] as $attribute) {
                $attrId = $attribute[$this->getAttributeIdentifier()];

                //We want merge items if attribute exists already
                if ( array_key_exists($attrId, $attributes) ) {
                    $attributes[$attrId]['$items'] = collect($attributes[$attrId]['$items'])
                                                            ->merge($attribute['$items'])
                                                            ->unique($this->getAttributesItemIdentifier())
                                                            ->toArray();
                } else {
                    $attributes[$attrId] = $attribute;
                }
            }

            if ( isset($row['$variants']) ) {
                $attributes = $this->getPreparedAttributes($row['$variants'], $attributes);
            }
        }

        return $attributes;
    }

    private function getPreparedAttributesItems($attributes)
    {
        $items = [];

        foreach ($attributes as $attribute) {
            foreach ($attribute['$items'] ?: [] as $item) {
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
                $item = [
                    $relationName => $this->getExistingRows($relationTable)[$row[$this->{$relationIdentifier}()]],
                    'attribute_id' => $this->getExistingRows('attributes')[$attribute[$this->getAttributeIdentifier()]],
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