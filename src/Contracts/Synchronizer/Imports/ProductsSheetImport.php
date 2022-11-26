<?php

namespace AdminEshop\Contracts\Synchronizer\Imports;

use AdminEshop\Contracts\Synchronizer\Concerns\HasPricesTrait;
use AdminEshop\Contracts\Synchronizer\Excel\SheetImportWrapper;
use AdminEshop\Contracts\Synchronizer\Imports\ProductsImport;
use AdminEshop\Contracts\Synchronizer\SynchronizerInterface;
use Illuminate\Support\Collection;
use Store;

class ProductsSheetImport extends ProductsImport implements SynchronizerInterface
{
    use HasPricesTrait;

    public $synchronizeProducts = [ 'create' => true, 'update' => true, 'delete' => false ];
    public $synchronizeVariants = [ 'create' => true, 'update' => true, 'delete' => false ];
    public $synchronizeCategories = [ 'create' => true, 'update' => true, 'delete' => false ];
    public $synchronizeGallery = [ 'create' => true, 'update' => true, 'delete' => false ];
    public $synchronizeAttributes = [ 'create' => true, 'update' => true, 'delete' => false ];
    public $synchronizeAttributesItems = [ 'create' => true, 'update' => true, 'delete' => false ];
    public $synchronizeProductAttributes = [ 'create' => true, 'update' => true, 'delete' => true ];

    public $separators = [
        'categories' => ';',
        'attributes' => ';',
    ];

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
            $hasVariants = count($items) > 1;

            $item = $this->getImportProduct([
                'name' => $items[0][$this->importer->getColumnNameByField('name')],
                'code_pairing' => $pairingColumn,
                'product_type' => $hasVariants ? 'variants' : 'regular',
                '$categories' => $this->getCategoriesList($items),
                '$variants' => $hasVariants ? $this->prepareVariants($items) : [],
                '$attributes' => $this->prepareAttributes($items[0], function($sheetColumnName, $column) use ($hasVariants) {
                    //Allow attributes only for parent products, or attributes defines as variant => false
                    return $hasVariants == false || ($column['variant'] ?? true) === false;
                }),
            ], $items, $pairingColumn);

            //If is regulat product type, we want add additional info
            if ( in_array($item['product_type'], ['regular']) ){
                $item = $item + $this->prepareProductItem($items[0]);
            }

            $products[] = $item;
        }

        return $products;
    }

    public function getImportProduct($array, $items, $key)
    {
        return $array;
    }

    public function getCategoriesList($items)
    {
        $categories = explode($this->separators['categories'], ($items[0][$this->importer->getColumnNameByField('$categories')] ?? '').'');

        return array_map(function($number){
            return (int)$number;
        }, array_unique(array_filter($categories)));
    }

    private function prepareVariants($items)
    {
        return collect($items)->map(function($variant){
            return [
                '$attributes' => $this->prepareAttributes($variant, function($sheetColumnName, $column){
                    //Allow attributes only for parent products, or attributes defines as variant => false
                    return ($column['variant'] ?? true) === true;
                }),
            ] + $this->prepareProductItem($variant, true);
        });
    }

    protected function prepareProductItem($item, $variant = false)
    {
        $array = [];

        $array = $this->prepareProductPrices($array, $item, $variant);

        if ( $variant === true ){
            $array['product_type'] = 'variant';
        }

        foreach ($this->importer->getCastedColumns() as $sheetColumnName => $column) {
            if ( !isset($column['column']) ) {
                continue;
            }

            if ( $variant === true && ($column['variants'] ?? true) === false ){
                continue;
            }

            //Does not overide built in values
            if ( !array_key_exists($column['column'], $array) ) {
                $value = $this->isEmptyValue($item[$sheetColumnName] ?? null) === false
                    ? $item[$sheetColumnName]
                    : ($this->isEmptyValue($column['default'] ?? null) === false
                        ? $column['default']
                        : null
                    );

                //Skip empty values
                if ( is_null($value) && ($column['skipEmpty'] ?? false) === true ){
                    continue;
                }

                $array[$column['column']] = $value;
            }
        }

        return $array;
    }

    private function isEmptyValue($value)
    {
        return is_null($value) || $value === '';
    }

    public function prepareAttributes($item, callable $filter = null)
    {
        $attributes = [];

        foreach ($this->importer->getCastedColumns() as $sheetColumnName => $column) {
            if ( !isset($column['attribute']) ) {
                continue;
            }

            //If attribute is empty
            if ( !($value = $item[$sheetColumnName] ?? null) ){
                continue;
            }

            //Custom Attributes filter
            if ( is_callable($filter) && $filter($sheetColumnName, $column) == false ){
                continue;
            }

            //Disable or enable separating imported items with ;
            $items = ($column['multiple'] ?? true) === true
                        ? explode($this->separators['attributes'], $value)
                        : [$value];

            $attribute = [
                'code' => $column['attribute'],
                'name' => $this->importer->array['header'][$sheetColumnName],
                '$items' => array_map(function($value) use ($column) {
                    return [
                        'name' => $value,
                        'code' => $column['attribute'].'_'.crc32($value),
                    ];
                }, $items),
            ];

            $attributes[] = $attribute;
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
