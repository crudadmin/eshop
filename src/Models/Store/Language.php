<?php

namespace AdminEshop\Models\Store;

use Admin\Models\Language as BaseLanguage;

class Language extends BaseLanguage
{
    public function mutateFields($fields)
    {
        parent::mutateFields($fields);

        $fields->push([
            'currency' => 'name:Predvolená mena pre jazykovú mutáciu|belongsTo:currencies,:name - :code',
        ]);
    }
}
