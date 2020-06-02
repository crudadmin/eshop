<?php

namespace AdminEshop\Models\Store;

use Gogol\Invoices\Model\Country as BaseCountry;
use Admin\Fields\Group;

class Country extends BaseCountry
{
    protected $group = 'settings.store';
}