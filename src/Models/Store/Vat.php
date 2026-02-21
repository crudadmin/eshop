<?php

namespace AdminEshop\Models\Store;

use Gogol\Invoices\Model\Vat as BaseVat;

class Vat extends BaseVat
{
    protected $group = 'store';

    /**
     * Cache support for vats
     *
     * @var array
     */
    protected $flushableCacheKeys = ['vats'];
}