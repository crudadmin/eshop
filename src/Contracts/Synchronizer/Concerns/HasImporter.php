<?php

namespace AdminEshop\Contracts\Synchronizer\Concerns;

use Admin;
use Admin\Eloquent\AdminModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Localization;
use Str;
use Throwable;

trait HasImporter
{
    protected $insertIncrement = [];

    protected $onCreate = [];
    protected $onUpdate = [];
    protected $onDeleteOrHide = [];
    protected $existingRows = [];
    protected $unpublishedRowsToPublish = [];

    private $displayedPercentage = [];

    public function getExistingRows($table)
    {
        return $this->existingRows[$table];
    }

    private function isMultiKey($fieldKey)
    {
        return is_array($fieldKey);
    }

    private function getIdentifierName($fieldKey)
    {
        return $this->isMultiKey($fieldKey) ? '_identifier' : $fieldKey;
    }

    public function bootExistingRows(AdminModel $model, $fieldKey, $allIdentifiers)
    {
        $selectcolumn = $this->isMultiKey($fieldKey)
                            ? ('CONCAT_WS(\'-\', IFNULL('.implode(', \'\'), IFNULL(', $fieldKey).', \'\')) as _identifier')
                            : $fieldKey;

        $existingRows = DB::table($model->getTable())
            ->selectRaw(implode(', ', array_filter([
                $model->getKeyName(), $selectcolumn, $model->getProperty('publishable') ? 'published_at' : null
            ])))
            ->when($model->hasSoftDeletes(), function($query){
                $query->whereNull('deleted_at');
            })
            ->when($this->isMultiKey($fieldKey) == false, function($query) use ($fieldKey, $allIdentifiers) {
                $query->whereIn($fieldKey, $allIdentifiers);
            })
            //We can use where in clause with conat support, but this is slow. Faster is to load all data...
            // ->when($this->isMultiKey($fieldKey), function($query) use ($fieldKey, $allIdentifiers) {
            //     $query->havingRaw('_identifier in (? '.str_repeat(', ?', count($allIdentifiers) - 1).')', $allIdentifiers);
            // })
            ->get();

        $this->existingRows[$model->getTable()] = $existingRows->pluck(
            $model->getKeyName(),
            $this->getIdentifierName($fieldKey)
        )->toArray();

        if ( $model->getProperty('publishable') ) {
            $rowsToPublish = $existingRows->whereNull('published_at')->filter(function($row) use ($fieldKey, $allIdentifiers) {
                //Single key identifier is passed, because we filter in database
                if ( !$this->isMultiKey($fieldKey) ) {
                    return true;
                }

                //If is multiidentifier, we need werify that identifier is in present identifiers $allIdentifiers variable
                return in_array($row->{$this->getIdentifierName($fieldKey)}, $allIdentifiers);
            })->pluck($model->getKeyName());
        } else {
            $rowsToPublish = [];
        }

        $this->unpublishedRowsToPublish[$model->getTable()] = $rowsToPublish;
    }

    private function isLocalizedField(AdminModel $model, $key)
    {
        return $this->cache($model->getTable().'.isLocale.'.$key, function() use ($model, $key){
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

        $this->applyMutators($model, $row);

        if ( method_exists($this, $castMethodName = ('set'.class_basename(get_class($model)).'CastData')) ){
            $row = $this->{$castMethodName}($row);
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

        $this->applyMutators($model, $row, null, 'setFinal');

        $this->removeHelperAttributes($row);

        if ( $model->isSortable() ){
            $row['_order'] = $this->insertIncrement[$model->getTable()];
        }

        if ( $model->hasSluggable() ){
            $slug = $row[$model->getProperty('sluggable')];

            $row['slug'] = $model->makeSlug($slug);
        }

        return $row;
    }

    private function getKeyMutatorMethodName(AdminModel $model, $key, $prefix = null)
    {
        return ($prefix ?: 'set').class_basename(get_class($model)).Str::studly($key).'Attribute';
    }

    public function castUpdateData(AdminModel $model, $row)
    {
        $row = $this->castData($model, $row);

        return $row;
    }

    public function postCastUpdateData(AdminModel $model, $row, $oldRow)
    {
        $this->applyMutators($model, $row, $oldRow, 'setFinal');

        $this->removeHelperAttributes($row);

        return $row;
    }

    public function getRowChanges(AdminModel $model, $row, $oldRow)
    {
        $changes = [];
        foreach ($row as $key => $value) {
            if ( $this->isSameValue($model, $key, $value, $oldRow->{$key} ?? null) == false ){
                $changes[$key] = $value;

                //create new slug if field of slug maker has been changed
                if ( $model->getProperty('sluggable') == $key ){
                    $changes['slug'] = $model->makeSlug($value);
                }
            }
        }

        return $changes;
    }

    private function applyMutators(AdminModel $model, &$row, $oldRow = null, $prefix = null)
    {
        //Apply mutators on changed inputs
        foreach ($row as $key => $value) {
            if ( $isHelperAttribute = (substr($key, 0, 1) == '$') ) {
                $key = str_replace('$', '', $key);
            }

            if ( method_exists($this, $this->getKeyMutatorMethodName($model, $key, $prefix)) ) {
                $value = $this->{$this->getKeyMutatorMethodName($model, $key, $prefix)}($value, $row, $oldRow);

                //Does not rewrite helper properties
                if ( $isHelperAttribute == false ) {
                    $row[$key] = $value;
                }
            }
        }
    }

    private function removeHelperAttributes(&$row)
    {
        $toRemove = [];

        foreach ($row as $key => $value) {
            if ( substr($key, 0, 1) == '$' ){
                unset($row[$key]);
            }
        }
    }

    private function keyByIdentifier($fieldKey)
    {
        if ( $this->isMultiKey($fieldKey) == false ){
            return $fieldKey;
        }

        return function($row) use ($fieldKey) {
            $values = [];

            foreach ($fieldKey as $key) {
                $values[] = $row[$key] ?? '';
            }

            return implode('-', $values);
        };
    }

    public function synchronize(AdminModel $model, $fieldKey, $rows)
    {
        $totalStart = Admin::start();
        $this->message('************* '.$model->getTable());

        $rows = collect($rows)->keyBy(
            $this->keyByIdentifier($fieldKey)
        );

        $allIdentifiers = $rows->keys()->toArray();

        $this->bootExistingRows($model, $fieldKey, $allIdentifiers);

        //Identify which rows should be updated and which created
        $this->onCreate[$model->getTable()] = array_diff($allIdentifiers, array_keys($this->getExistingRows($model->getTable())));
        $this->onUpdate[$model->getTable()] = array_diff($rows->keys()->toArray(), $this->onCreate[$model->getTable()]);
        $this->onDeleteOrHide[$model->getTable()] = $this->getDeletionRows($model, $fieldKey, $rows->keys()->toArray());

        $this->message(
             'On create rows: '.count($this->onCreate[$model->getTable()]) ."\n"
            .'Existing rows: '.count($this->onUpdate[$model->getTable()])."\n"
            .'On '.($model->getProperty('publishable') ? 'unpublish' : 'delete').': '.count($this->onDeleteOrHide[$model->getTable()])."\n"
            .'On publish: '.count($this->unpublishedRowsToPublish[$model->getTable()])
            ."\n"
        );

        //Create new rows
        $this->createRows($model, $rows, $fieldKey);

        //Update existing rows
        $this->updateRows($model, $rows, $fieldKey);

        $this->hideOrUnpublish($model);

        $this->publishMissing($model);

        $this->message('************* Import successfull in '.Admin::end($totalStart)."\n");
    }

    private function createRows(AdminModel $model, $rows, $fieldKey)
    {
        $start = Admin::start();

        $insert = [];

        $this->insertIncrement[$model->getTable()] = $model->isSortable() ? $model->getNextOrderIncrement() : 0;

        $total = count($this->onCreate[$model->getTable()]);
        $inserted = 0;

        foreach ($this->onCreate[$model->getTable()] as $i => $onCreateIdentifier) {
            try {
                $originalRow = $rows[$onCreateIdentifier];
                $row = $this->castInsertData($model, $originalRow);

                $id = $model->insertGetId($row);

                $this->existingRows[$model->getTable()][$onCreateIdentifier] = $id;

                $this->info('- inserted '.($inserted+1).'/'.$total.' - ['.$onCreateIdentifier.']');

                $this->runAfterMethod($model, $originalRow, $id);

                $inserted++;
            } catch(Throwable $e){
                $this->error('[create error] '.$e->getMessage().' in '.str_replace(base_path(), '', $e->getFile()).':'.$e->getLine());

                //If debug is turned on
                if ( env('APP_DEBUG') === true ){
                    throw $e;
                }
            }
        }

        $this->message('> Inserted '.$inserted.' from '.$total.' successfully in '.Admin::end($start).'s');
    }

    private function getDeletionRows(AdminModel $model, $fieldKey, $allIdentifiers)
    {
        $selectFieldKeyColumn = $this->isMultiKey($fieldKey)
                            ? ('CONCAT_WS(\'-\', IFNULL('.implode(', \'\'), IFNULL(', $fieldKey).', \'\')) as _identifier')
                            : $fieldKey;

        return DB::table($model->getTable())
            ->selectRaw($model->getKeyName().', '.$selectFieldKeyColumn)
            ->when($model->hasSoftDeletes(), function($query){
                $query->whereNull('deleted_at');
            })
            ->when($model->getProperty('publishable'), function($query){
                $query->whereNotNull('published_at');
            })
            //Select only keys not present in given identifiers
            ->when($this->isMultiKey($fieldKey) == false, function($query) use ($fieldKey, $allIdentifiers) {
                $query->whereNotIn($fieldKey, $allIdentifiers);
            })
            //We can use where in clause with conat support, but this is slow. Faster is to load all data...
            ->when($this->isMultiKey($fieldKey), function($query) use ($fieldKey, $allIdentifiers) {
                $query->havingRaw('_identifier not in (? '.str_repeat(', ?', count($allIdentifiers) - 1).')', $allIdentifiers);
            })
            ->pluck($this->getIdentifierName($fieldKey), $model->getKeyName())
            ->toArray();
    }

    private function hideOrUnpublish($model)
    {
        $toRemove = array_keys($this->onDeleteOrHide[$model->getTable()]);

        if ( count($toRemove) == 0 ){
            return;
        }

        $query = DB::table($model->getTable())->whereIn($model->getKeyName(), $toRemove);

        if ( $model->getProperty('publishable') ){
            $query->update([
                'published_at' => null,
            ]);
        } else if ( $model->hasSoftDeletes() ) {
            $query->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        $this->message('> '.($model->getProperty('publishable') ? 'Unpublished' : 'Deleted').' '.count($toRemove).' successfully.');
    }

    private function publishMissing($model)
    {
        $toPublish = $this->unpublishedRowsToPublish[$model->getTable()];

        if ( count($toPublish) == 0 ){
            return;
        }

        $query = DB::table($model->getTable())->whereIn($model->getKeyName(), $toPublish)->update([
            'published_at' => Carbon::now()
        ]);

        $this->message('- Missing '.count($toPublish).' rows published successfully.');
    }

    private function runAfterMethod($model, $originalRow, $id)
    {
        $afterMethodName = ('fireAfter'.class_basename(get_class($model)));

        if ( method_exists($this, $afterMethodName) ){
            $this->{$afterMethodName}($originalRow, $id);
        }
    }

    private function getUpdateColumns(AdminModel $model, $rows)
    {
        $columns = [];

        foreach ($rows as $row) {
            $newKeys = array_diff(array_keys($row), $columns);

            if ( count($newKeys) ) {
                $columns = array_merge($columns, $newKeys);
            }
        }

        //We need remove ghost keys
        $columns = array_flip($columns);
        $this->removeHelperAttributes($columns);

        $columns = array_merge([$model->getKeyName()], array_flip($columns));

        return $columns;
    }

    private function updateRows(AdminModel $model, $rows, $fieldKey)
    {
        $start = Admin::start();

        $this->displayedPercentage[$model->getTable()] = [];

        $chunks = array_chunk($this->onUpdate[$model->getTable()], 1000, true);

        $total = count($this->onUpdate[$model->getTable()]);
        $i = 0;
        $checked = 0;
        $updated = 0;
        $totalUpdated = 0;
        $totalChecked = 0;

        $modelKeyName = $model->getKeyName();

        foreach ($chunks as $chunkRows) {
                //Select all available columns from all rows
                $rowsDataWithIdsKeys = collect(array_map(function($onUpdateIdentifier) use ($model, $rows) {
                    return $this->castUpdateData($model, $rows[$onUpdateIdentifier]) + [
                        $model->getKeyName() => $this->getExistingRows($model->getTable())[$onUpdateIdentifier]
                    ];
                }, $chunkRows))->keyBy($modelKeyName)->toArray();

                //We want select all neccessary data for given ids set of actual chunk
                $dbRows = DB::table($model->getTable())->select(
                    $this->getUpdateColumns($model, $rowsDataWithIdsKeys)
                )
                ->when($model->hasSoftDeletes(), function($query){
                    $query->whereNull('deleted_at');
                })
                ->whereIn($modelKeyName, array_keys($rowsDataWithIdsKeys))
                ->get();

                foreach ($dbRows as $dbRow) {
                    try {
                        $i++;
                        $totalChecked++;

                        $percentage = (int)round(100 / $total * $i);

                        if ( ($percentage) % 10 == 0 && in_array($percentage, $this->displayedPercentage[$model->getTable()]) === false ){
                            $this->info('- checked '.(round($percentage) == 100 ? $total : $i).' / '.$total.' ('.$percentage.'%)');
                            $this->displayedPercentage[$model->getTable()][] = $percentage;
                        }

                        $castedImportRow = $rowsDataWithIdsKeys[$dbRow->{$modelKeyName}];

                        $rowChanges = $this->getRowChanges($model, $castedImportRow, $dbRow);
                        $rowChanges = $this->postCastUpdateData($model, $rowChanges, $dbRow);

                        //Update row if something has been changed
                        if ( count($rowChanges) > 0 ){

                            //Only if ID is available
                            if ( $id = $dbRow->{$modelKeyName} ) {
                                $totalUpdated++;

                                DB::table($model->getTable())->where($modelKeyName, $id)->update($rowChanges);

                                $updated++;
                            }
                        }

                        $checked++;
                    } catch(Throwable $e){
                        $this->error('[update error] '.$e->getMessage().' in '.str_replace(base_path(), '', $e->getFile()).':'.$e->getLine());

                        //If debug is turned on
                        if ( env('APP_DEBUG') === true ){
                            throw $e;
                        }
                    }
                }
        }

        $this->message('> Checked '.$checked.' from '.$totalChecked.' | updated '.$updated.' from '.$totalUpdated.' successfully in '.Admin::end($start).'s');
    }
}