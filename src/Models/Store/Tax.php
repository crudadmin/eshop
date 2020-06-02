<?php

namespace AdminEshop\Models\Store;

use Gogol\Invoices\Model\Tax as BaseTax;
use Admin\Fields\Group;

class Tax extends BaseTax
{
    protected $group = 'settings.store';
}