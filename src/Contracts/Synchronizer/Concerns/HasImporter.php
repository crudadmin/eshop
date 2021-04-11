<?php

namespace AdminEshop\Contracts\Synchronizer\Concerns;

use Admin\Eloquent\AdminModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Localization;
use Str;
use Admin;
use Throwable;

trait HasImporter
{
    protected $insertIncrement = 0;

    protected $onCreate = [];

    protected $existingRows = [];

    public function getExistingRows()
    {
        return $this->existingRows;
    }

    public function bootExistingRows(AdminModel $model, $fieldKey, $allIdentifiers)
    {
        $this->existingRows = DB::table($model->getTable())
                                ->select($model->getKeyName(), $fieldKey)
                                ->whereIn($fieldKey, $allIdentifiers)
                                ->pluck($model->getKeyName(), $fieldKey)
                                ->toArray();
    }

    private function isLocalizedField(AdminModel $model, $key)
    {
        return $this->cache($model->getTable().'.isLocale'.$key, function() use ($model, $key){
            return $model->hasFieldParam($key, 'locale', true);
        });
    }

    public function castData(AdminModel $model, $row)
    {
        foreach ($row as $key => $value) {
            $isLocale = $this->isLocalizedField($model, $key);

            if ( $isLocale ){
                if ( is_string($value) ) {
                    $defaultLocaleSlug = Localization::get()->slug;

                    $row[$key] = [
                        $defaultLocaleSlug => $value
                    ];
                }

                //Cast json data
                $row[$key] = array_filter($row[$key]);
                $row[$key] = $this->encodeJsonArray($row[$key]);
            }
        }

        if ( method_exists($this, 'setCastData') ){
            $row = $this->setCastData($row);
        }

        return $row;
    }

    private function encodeJsonArray($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function isSameValue(AdminModel $model, $key, $value, $oldValue)
    {
        if ( $this->isLocalizedField($model, $key) ) {
            return $value == $this->encodeJsonArray(json_decode($oldValue));
        }

        return $value == $oldValue;
    }

    public function castInsertData(AdminModel $model, $row)
    {
        $row = $this->castData($model, $row);

        $this->applyMutators($row);

        if ( $model->isSortable() ){
            $row['_order'] = $this->insertIncrement;
        }

        if ( $model->hasSluggable() ){
            $slug = $row[$model->getProperty('sluggable')];

            $row['slug'] = $model->makeSlug($slug);
        }

        return $row;
    }

    private function getKeyMutatorMethodName($key)
    {
        return 'set'.Str::studly($key).'Attribute';
    }

    public function castUpdateData(AdminModel $model, $row, $oldRow)
    {
        $row = $this->castData($model, $row);

        $changes = [];
        foreach ($row as $key => $value) {
            if ( $this->isSameValue($model, $key, $value, $oldRow->{$key}) == false ){
                $changes[$key] = $value;

                //create new slug if field of slug maker has been changed
                if ( $model->getProperty('sluggable') == $key ){
                    $changes['slug'] = $model->makeSlug($value);
                }
            }
        }

        $this->applyMutators($changes, $row, $oldRow);

        return $changes;
    }

    private function applyMutators(&$row, $oldRow = null)
    {
        //Apply mutators on changed inputs
        foreach ($row as $key => $value) {
            if ( method_exists($this, $this->getKeyMutatorMethodName($key)) ) {
                $row[$key] = $this->{$this->getKeyMutatorMethodName($key)}($value, $row, $oldRow);
            }
        }
    }

    public function synchronize(AdminModel $model, $fieldKey, $rows)
    {
        $rows = collect($rows)->keyBy($fieldKey);
        $allIdentifiers = $rows->keys()->toArray();

        $this->bootExistingRows($model, $fieldKey, $allIdentifiers);

        //Identify which rows should be updated and which created
        $this->onCreate = array_diff($allIdentifiers, array_keys($this->existingRows));
        $this->onUpdate = array_diff($rows->keys()->toArray(), $this->onCreate);
        $this->message('On create '.count($this->onCreate).' rows / On update '.count($this->onUpdate).' rows');

        //Create new rows
        $start = Admin::start();
        $this->createRows($model, $rows, $fieldKey);
        $this->message('Created successfully in '.Admin::end($start).'s');

        //Update existing rows
        $start = Admin::start();
        $this->updateRows($model, $rows, $fieldKey);
        $this->message('Updated successfully in '.Admin::end($start).'s');
    }

    private function createRows(AdminModel $model, $rows, $fieldKey)
    {
        $insert = [];

        $this->insertIncrement = $model->isSortable() ? $model->getNextOrderIncrement() : 0;

        $total = count($this->onCreate);

        foreach ($this->onCreate as $i => $onCreateIdentifier) {
            try {
                $row = $this->castInsertData($model, $rows[$onCreateIdentifier]);

                $id = $model->insertGetId($row);

                $this->existingRows[$row[$fieldKey]] = $id;

                $this->message('[created '.$i.'/'.$total.'] ['.$model->getTable().'] ['.$onCreateIdentifier.']');
            } catch(Throwable $e){
                $this->error('[create error] '.$e->getMessage().' in '.str_replace(base_path(), '', $e->getFile()).':'.$e->getLine());
            }
        }
    }

    private function getUpdateColumns(AdminModel $model, $rows)
    {
        $columns = [
            $model->getKeyName()
        ];

        foreach ($rows as $row) {
            $newKeys = array_diff(array_keys($row), $columns);

            if ( count($newKeys) ) {
                $columns = array_merge($columns, $newKeys);
            }
        }

        return $columns;
    }

    private function updateRows(AdminModel $model, $rows, $fieldKey)
    {
        $chunks = array_chunk($this->onUpdate, 1000);

        foreach ($chunks as $chunkRows) {
            try {
                //Select all available columns from all rows
                $rowsData = array_map(function($onUpdateIdentifier) use ($rows) {
                    return $rows[$onUpdateIdentifier];
                }, $chunkRows);

                $dbRows = DB::table($model->getTable())->select(
                    $this->getUpdateColumns($model, $rowsData)
                )->get();

                foreach ($dbRows as $dbRow) {
                    $unioRow = $rows[$dbRow->{$fieldKey}];

                    $rowChanges = $this->castUpdateData($model, $unioRow, $dbRow);

                    //Update row if something has been changed
                    if ( count($rowChanges) > 0 ){
                        $keyName = $model->getKeyName();

                        //Only if ID is available
                        if ( $id = $dbRow->{$keyName} ) {
                            DB::table($model->getTable())->where($keyName, $id)->update($rowChanges);
                        }
                    }
                }
            } catch(Throwable $e){
                $this->error('[update error] '.$e->getMessage().' in '.str_replace(base_path(), '', $e->getFile()).':'.$e->getLine());
            }
        }
    }
}