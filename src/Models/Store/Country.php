<?php

namespace AdminEshop\Models\Store;

use Gogol\Invoices\Model\Country as BaseCountry;
use Admin\Fields\Group;

class Country extends BaseCountry
{
    protected $group = 'store';

    public function mutateFields($fields)
    {
        parent::mutateFields($fields);

        $fields->push([
            'iso3166' => 'name:Číselný kód krajiny (ISO 3166)|title:https://sk.wikipedia.org/wiki/ISO_3166-1 - Potrebné pre DPD'
        ]);
    }

    public function setBootstrapResponse()
    {
        return $this->setVisible(['id', 'name', 'code']);
    }
}