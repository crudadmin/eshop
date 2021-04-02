<?php

namespace AdminEshop\Models\Store;

use Gogol\Invoices\Model\Vat as BaseVat;
use Admin\Fields\Group;

class Vat extends BaseVat
{
    protected $group = 'store';
}