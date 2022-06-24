<?php

namespace AdminEshop\Models\Invoice;

use OrderService;
use Gogol\Invoices\Model\InvoicesItem as BaseInvoicesItem;

class InvoicesItem extends BaseInvoicesItem
{
    public function mutateFields($fields)
    {
        $fields->push([
            'identifier' => 'name:Order item identifier|inaccessible',
        ]);
    }
}