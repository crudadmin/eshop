<?php

namespace AdminEshop\Contracts\Synchronizer\Excel;

use AdminEshop\Contracts\Synchronizer\Excel\FormattingError;
use AdminEshop\Contracts\Synchronizer\Imports\ProductsSheetImport;
use AdminEshop\Models\Store\SynchronizerReport;
use Admin\Core\Helpers\File;
use Admin\Eloquent\AdminModel;

class SheetImportWrapper
{
    public $file;

    public $array;

    public function getColumns()
    {
        return [
            //General
            'ean_code' => [ 'column' => 'ean' ],
            'sku_number_match_code' => [ 'column' => 'code', 'required' => true ],
            'pairing_symbol' => [ 'column' => 'code_pairing', 'variants' => false ],
            'model_name' => [ 'column' => 'name', 'required' => true  ],
            'price' => [ 'column' => 'price', 'required' => true, ],
            'tax' => [ 'column' => '$vat_number', 'default' => 21 ],
            'category_id' => [ 'column' => '$categories' ],

            //Attributes
            // 'brand' => [ 'attribute' => 'brand', 'required' => true, ],
            // 'collection' => [ 'attribute' => 'collection' ],

            //Custom
            // 'partner_id' => [ 'column' => '$partners', 'default' => env('IMPORT_DEFAULT_PARTNER_ID') ],
        ];
    }

    public function getImporter()
    {
        return ProductsSheetImport::class;
    }

    public function __construct(File $file)
    {
        $this->file = $file;

        $this->array = (new FromXlsToArray($file))->toArray();
    }

    public function import(AdminModel $inventory)
    {
        $insert = collect();

        if ( !$this->getPairingCodeColumnName() ){
            throw new FormattingError('Pairing symbol is not available.');
        }

        $rows = $this->getRows();
        $class = $this->getImporter();

        (new SynchronizerReport)->makeReport('Synchronizácia produktov', [
            new $class($rows, $this),
        ]);
    }

    public function getRows()
    {
        return collect($this->array['rows']);
    }

    public function getColumnNameByField($key)
    {
        return collect($this->getColumns())->where('column', $key)->keys()[0] ?? null;
    }

    public function getPairingCodeColumnName()
    {
        return $this->getColumnNameByField('code_pairing');
    }

    public function checkColumnsAviability()
    {
        $columns = $this->getColumns();

        $errors = [];

        foreach ($columns as $columnSheetKey => $column) {
            if ( ($column['required'] ?? false) === false ){
                continue;
            }

            if ( !array_key_exists($columnSheetKey, $this->array['header']) ){
                $errors[] = $columnSheetKey;
            }
        }

        if ( count($errors) ){
            throw new FormattingError(
                'V súbore sme nenašli potrebné stĺpce:<br>'.
                '<strong>'.implode(' | ', $errors).'</strong>'
            );
        }
    }
}
