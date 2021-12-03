<?php

namespace AdminEshop\Contracts\Synchronizer\Imports;

use AdminEshop\Contracts\Synchronizer\Excel\SheetImportWrapper;
use AdminEshop\Contracts\Synchronizer\Imports\ProductsImport;
use AdminEshop\Contracts\Synchronizer\SynchronizerInterface;
use Illuminate\Support\Collection;

class ProductsSheetImport extends ProductsImport implements SynchronizerInterface
{
    public $synchronizeProducts = [ 'create' => true, 'update' => true, 'delete' => false ];
    public $synchronizeVariants = [ 'create' => true, 'update' => true, 'delete' => false ];
    public $synchronizeCategories = [ 'create' => true, 'update' => true, 'delete' => false ];
    public $synchronizeGallery = [ 'create' => true, 'update' => true, 'delete' => false ];
    public $synchronizeAttributes = [ 'create' => true, 'update' => true, 'delete' => false ];
    public $synchronizeAttributesItems = [ 'create' => true, 'update' => true, 'delete' => false ];
    public $synchronizeProductAttributes = [ 'create' => true, 'update' => true, 'delete' => true ];

    protected $treeProductIdentifier = [];
    protected $sheetRows;
    protected $importer;

    public function __construct(Collection $sheetRows, SheetImportWrapper $importer)
    {
        $this->importer = $importer;
        $this->sheetRows = $sheetRows;
    }

    public function getProductIdentifier()
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

    public function getProductsGalleryIdentifier()
    {
        return 'id';
    }

    public function prepare() : array
    {
        return $this->prepareProducts();
    }

    private function prepareProducts()
    {
        //Prepare tree
        $this->prepareProductsTreeWithMappedKeys();

        $products = [];

        foreach ($this->treeProductIdentifier as $pairingColumn => $items) {
            $item = [
                'name' => $items[0][$this->importer->getColumnNameByField('name')],
                'code_pairing' => $pairingColumn,
                'product_type' => count($items) > 1 ? 'variants' : 'regular',
                '$categories' => $this->getCategoriesList($items),
                '$variants' => count($items) > 1 ? $this->prepareVariants($items) : [],
            ];

            //If is regulat product type, we want add additional info
            if ( in_array($item['product_type'], ['regular']) ){
                $item = $item + $this->prepareProductItem($items[0]);
            }

            $products[] = $item;
        }

        return $products;
    }

    public function getCategoriesList($items)
    {
        $categories = explode(';', ($items[0][$this->importer->getColumnNameByField('$categories')] ?? '').'');

        return array_map(function($number){
            return (int)$number;
        }, array_unique(array_filter($categories)));
    }

    private function prepareVariants($items)
    {
        return collect($items)->map(function($variant){
            return $this->prepareProductItem($variant, true);
        });
    }

    private function prepareProductItem($item, $variant = false)
    {
        $array = [
            '$attributes' => $this->prepareAttributes($item),
        ];

        foreach ($this->importer->getColumns() as $sheetColumnName => $column) {
            if ( !isset($column['column']) ) {
                continue;
            }

            if ( $variant === true && ($column['variants'] ?? true) === false ){
                continue;
            }

            //Does not overide built in values
            if ( !array_key_exists($column['column'], $array) ) {
                $array[$column['column']] = $item[$sheetColumnName] ?? $column['default'] ?? null;
            }
        }

        return $array;
    }

    public function prepareAttributes($item)
    {
        $attributes = [];

        foreach ($this->importer->getColumns() as $sheetColumnName => $column) {
            if ( !isset($column['attribute']) ) {
                continue;
            }

            //If attribute is empty
            if ( !($item[$sheetColumnName] ?? null) ){
                continue;
            }

            $attributes[] = [
                'code' => $column['attribute'],
                'name' => $this->importer->array['header'][$sheetColumnName],
                '$items' => [
                    [
                        'name' => $item[$sheetColumnName],
                        'code' => $column['attribute'].'_'.crc32($item[$sheetColumnName]),
                    ]
                ],
            ];
        }

        return $attributes;
    }

    private function prepareProductsTreeWithMappedKeys()
    {
        $pairingCodeColumn = $this->importer->getPairingCodeColumnName();

        $this->treeProductIdentifier = $this->sheetRows->whereNotNull($pairingCodeColumn)->groupBy($pairingCodeColumn);

        return $this->treeProductIdentifier;
    }
}
