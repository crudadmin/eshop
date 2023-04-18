<?php

namespace AdminEshop\Admin\Modules;

use AdminEshop\Admin\Rules\SetSearchIndexRule;
use Admin\Core\Eloquent\Concerns\AdminModelModule;
use Admin\Core\Eloquent\Concerns\AdminModelModuleSupport;

class SearchModule extends AdminModelModule implements AdminModelModuleSupport
{
    public function isActive($model)
    {
        return !$model->getProperty('localeSearch') ? false : true;
    }

    public function setRulesProperty($rules = [])
    {
        $rules = array_merge($rules ?: [], [
            SetSearchIndexRule::class,
        ]);

        return $rules;
    }

    public function mutateFields($fields)
    {
        $searchableFields = $this->getModel()->getProperty('localeSearch');

        $indexField = [
            'fulltext_index' => 'name:Fulltext index|inaccessible|type:text|fulltext',
        ];

        if ( is_array($searchableFields) ) {
            $fields->after($searchableFields[0], $indexField);
        } else {
            $fields->push($indexField);
        }
    }
}
