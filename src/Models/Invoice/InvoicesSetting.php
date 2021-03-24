<?php

namespace AdminEshop\Models\Invoice;

use OrderService;
use Gogol\Invoices\Model\InvoicesSetting as BaseInvoicesSetting;

class InvoicesSetting extends BaseInvoicesSetting
{
    public function active()
    {
        return OrderService::hasInvoices();
    }
}