<?php

namespace AdminEshop\Contracts\Synchronizer\Imports;

use Admin;
use AdminEshop\Contracts\Synchronizer\Synchronizer;
use AdminEshop\Contracts\Synchronizer\SynchronizerInterface;
use AdminEshop\Models\Products\Pivot\ProductsAttributesItem;
use AdminEshop\Models\Products\Pivot\ProductsCategoriesPivot;
use AdminEshop\Models\Products\ProductsGallery;
use DB;
use Store;

class ProductsImport extends Synchronizer implements SynchronizerInterface
{
    public $synchronizeProducts = ['create' => true, 'update' => true, 'delete' => true];
    public $synchronizeVariants = ['create' => true, 'update' => true, 'delete' => true];
    public $synchronizeCategories = ['create' => true, 'update' => true, 'delete' => true];
    public $synchronizeGallery = ['create' => true, 'update' => true, 'delete' => true];
    public $synchronizePrices = ['create' => true, 'update' => true, 'delete' => true];
    public $synchronizeAttributes = ['create' => true, 'update' => true, 'delete' => true];
    public $synchronizeAttributesItems = ['create' => true, 'update' => true, 'delete' => true];
    public $synchronizeProductAttributes = ['create' => true, 'update' => true, 'delete' => true];

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
        return array_filter([
            'product_id' => 'products',
            'code'
        ]);
    }

    public function getProductsPricesIdentifier()
    {
        return array_filter([
            'product_id' => 'products',
            'currency_id',
            'vat_id',
        ]);
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
        return [
            'product_id' => 'products',
            'attributes_item_id'
        ];
    }

    public function handle(array $rows = null)
    {
        if ( $this->synchronizeProducts ) {
            $this->synchronize(
                Admin::getModel('Product'),
                $this->getProductIdentifier(),
                $this->cleanProducts($rows),
                $this->synchronizeProducts,
                function($query){
                    $query->whereNull('product_id');
                }
            );
        }

        if ( $this->synchronizeVariants ) {
            $this->synchronize(
                Admin::getModel('Product'),
                $this->getProductsVariantIdentifier(),
                $this->getPreparedVariants($rows),
                $this->synchronizeVariants,
                function($query){
                    $query->whereNotNull('product_id');
                }
            );
        }

        if ( $this->synchronizeCategories && Store::hasCategories() ) {
            $this->synchronize(
                new ProductsCategoriesPivot,
                ['product_id' => 'products', 'category_id'],
                $this->getPreparedProductsCategories($rows),
                $this->synchronizeCategories
            );
        }

        if ( $this->synchronizePrices ) {
            $this->synchronize(
                Admin::getModel('ProductsPrice'),
                $this->getProductsPricesIdentifier(),
                $this->getPreparedPrices($rows),
                $this->synchronizePrices
            );
        }

        if ( $this->synchronizeGallery ) {
            $this->synchronize(
                Admin::getModel('ProductsGallery'),
                $this->getProductsGalleryIdentifier(),
                $this->getPreparedGallery($rows),
                $this->synchronizeGallery
            );
        }

        if ( $this->synchronizeAttributes ) {
            $this->synchronize(
                Admin::getModel('Attribute'),
                $this->getAttributeIdentifier(),
                $preparedAttributes = $this->getPreparedAttributes($rows),
                $this->synchronizeAttributes
            );
        }

        if ( $this->synchronizeAttributes ) {
            $this->synchronize(
                Admin::getModel('AttributesItem'),
                $this->getAttributesItemIdentifier(),
                $this->getPreparedAttributesItems($preparedAttributes),
                $this->synchronizeAttributes
            );
        }

        if ( $this->synchronizeProductAttributes ) {
            $this->synchronize(
                new ProductsAttributesItem,
                $this->getProductsAttributeIdentifier(),
                $this->getPreparedProductsAttribute($rows),
                $this->synchronizeProductAttributes
            );
        }
    }

    private function cleanProducts($rows)
    {
        foreach ($rows as $row) {
            //If product type is not variants type, and product id has not been set,
            //then we want reset product id, because previously this product may be variant.
            if ( ($row['product_type'] ?? null) !== 'variants' && !isset($row['product_id']) ){
                $row['product_id'] = null;
            }
        }

        return $rows;
    }

    public function getCategories()
    {
        return $this->cache('import.categories', function(){
            $model = Admin::getModel('Category');
            $columns = ['id', $this->getCategoryIdentifier()];

            if ( $model->getProperty('belongsToModel') ){
                $columns[] = 'category_id';
            }

            return $model->select($columns)->withUnpublished()->get();
        });
    }

    private function getPreparedVariants($rows)
    {
        $variants = [];

        foreach ($rows as $product) {
            foreach ($product['$variants'] ?? [] as $variant) {
                $variants[] = $variant + [
                    'product_type' => ($variant['product_type'] ?? null) ?: 'variant',
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

    private function getPreparedGallery($rows, $gallery = [], $relationIdentifier = 'getProductIdentifier')
    {
        foreach ($rows as $row) {
            if ( isset($row['$variants']) && count($row['$variants']) ) {
                $gallery = $this->getPreparedGallery(
                    $row['$variants'],
                    $gallery,
                    'getProductsVariantIdentifier'
                );
            }

            foreach ($row['$gallery'] ?? [] as $image) {
                $image['product_id'] = $this->getExistingRows('products')[
                    $row[$this->{$relationIdentifier}()]
                ];

                $gallery[] = $image;
            }
        }

        return $gallery;
    }

    private function getPreparedPrices($rows, $levels = [], $relationIdentifier = 'getProductIdentifier')
    {
        foreach ($rows as $row) {
            if ( isset($row['$variants']) && count($row['$variants']) ) {
                $levels = $this->getPreparedPrices(
                    $row['$variants'],
                    $levels,
                    'getProductsVariantIdentifier'
                );
            }

            foreach ($row['$prices'] ?? [] as $price) {
                $levels[] = [
                    'product_id' => $this->getExistingRows('products')[
                        $row[$this->{$relationIdentifier}()]
                    ],
                    'currency_id' => $price['currency_id'],
                    'price' => $price['price'],
                    'vat_id' => $this->getVatIdByValue($price['vat']),
                ];
            }
        }

        return $levels;
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

    private function getPreparedProductsAttribute($rows, $productsAttribute = [], $relationIdentifier = 'getProductIdentifier')
    {
        foreach ($rows as $row) {
            foreach ($row['$attributes'] ?? [] as $attribute) {
                $itemString = implode(';', array_map(function($item){
                    return $item[$this->getAttributesItemIdentifier()];
                }, $attribute['$items']));
                $itemHash = crc32($itemString);

                foreach ($attribute['$items'] as $item) {
                    $identifier = $item[$this->getAttributesItemIdentifier()];

                    $item = [
                        'product_id' => $this->getExistingRows('products')[$row[$this->{$relationIdentifier}()]],
                        'attributes_item_id' => $this->getExistingRows('attributes_items')[$identifier],
                    ];

                    $productsAttribute[] = $item;
                }

            }

            if ( isset($row['$variants']) && count($row['$variants']) ){
                $productsAttribute = $this->getPreparedProductsAttribute(
                    $row['$variants'],
                    $productsAttribute,
                    'getProductsVariantIdentifier'
                );
            }
        }

        return $productsAttribute;
    }

    public function setProductVatNumberAttribute($value, &$row)
    {
        $row['vat_id'] = $this->getVatIdByValue($value);
    }

    public function setProductsVariantVatNumberAttribute($value, &$row)
    {
        $row['vat_id'] = $this->getVatIdByValue($value);
    }
}