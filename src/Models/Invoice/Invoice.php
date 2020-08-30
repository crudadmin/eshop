<?php

namespace AdminEshop\Models\Invoice;

use OrderService;
use Gogol\Invoices\Model\Invoice as BaseInvoice;

class Invoice extends BaseInvoice
{
    protected $migration_date = '2017-02-21 21:58:52';

    public function active()
    {
        return OrderService::hasInvoices();
    }

    public function mutateFields($fields)
    {
        $fields->push([
            'order' => 'name:Objednávka č.|invisible|belongsTo:orders,id',
        ]);
    }
}