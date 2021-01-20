<?php

namespace AdminEshop\Models\Invoice;

use OrderService;
use Gogol\Invoices\Model\InvoicesExport as BaseInvoicesExport;

class InvoicesExport extends BaseInvoicesExport
{
    public function active()
    {
        return OrderService::hasInvoices();
    }
}