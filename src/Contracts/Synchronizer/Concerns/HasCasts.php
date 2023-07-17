<?php

namespace AdminEshop\Contracts\Synchronizer\Concerns;

use Localization;
use Illuminate\Database\Eloquent\Model;

trait HasCasts
{
    public function getLocalization()
    {
        return $this->cache('locale', function(){
            return Localization::getFirstLanguage();
        });
    }

    public function getLocaleSlug()
    {
        return $this->getLocalization()->slug;
    }

    public function castData(Model $model, $row)
    {
        foreach ($row as $key => $value) {
            $isLocale = $this->isLocalizedField($model, $key);

            if ( $isLocale ){
                if ( is_string($value) || is_numeric($value) ) {
                    $row[$key] = [
                        $this->getLocaleSlug() => $value
                    ];
                }

                if ( is_array($row[$key]) ) {
                    //Cast json data
                    $row[$key] = array_filter($row[$key], function($item){
                        return is_null($item) === false && $item !== '';
                    });
                }

                $row[$key] = $this->encodeJsonArray($row[$key]);
            }
        }

        $this->applyFieldMutators($model, $row);

        if ( method_exists($this, $castMethodName = ('set'.class_basename(get_class($model)).'Data')) ){
            $row = $this->{$castMethodName}($row);
        }

        return $row;
    }

    public function castInsertData(Model $model, $row)
    {
        if ( method_exists($this, $castMethodName = ('set'.class_basename(get_class($model)).'InsertData')) ){
            $row = $this->{$castMethodName}($row);
        }

        $row = $this->castData($model, $row);

        $row = $this->castFinalFieldsData($model, $row);

        if ( $this->isSortable($model) ){
            $row['_order'] = $this->insertIncrement[$model->getTable()];
        }

        if ( $this->hasSluggable($model) ){
            $slug = $row[$model->getProperty('sluggable')] ?? null;

            if ( !is_null($slug) && !empty(trim($slug)) ) {
                $row['slug'] = $model->makeSlug($slug);
            }
        }

        return $row;
    }


    public function castUpdateData(Model $model, $row)
    {
        $row = $this->castData($model, $row);

        return $row;
    }


    public function castFinalFieldsData(Model $model, $row, $oldRow = null)
    {
        $this->applyFieldMutators($model, $row, $oldRow, 'setFinal');

        $this->removeHelperAttributes($row);

        $this->addOtherLocalesIntoUpdatedRow($model, $row, $oldRow);

        return $row;
    }

    private function addOtherLocalesIntoUpdatedRow($model, &$row, $oldRow)
    {
        foreach ($row as $key => $origNewValue) {
            if ( !$this->isLocalizedField($model, $key) ){
                continue;
            }

            $oldValue = $oldRow && ($oldValue = $oldRow->{$key} ?? null) ? json_decode($oldValue, true) : [];
            $newValue = $origNewValue ? (json_decode($origNewValue, true) ?: []) : [];
            $finalValue = array_merge($oldValue, $newValue);

            $row[$key] = $this->encodeJsonArray($finalValue);
        }
    }

    public function encodeJsonArray($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}