<?php

namespace AdminEshop\Admin\Buttons;

use OrderService;
use Admin\Contracts\Exports\AdminButtonExport;

class GenerateInvoice extends AdminButtonExport
{
    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct($row = null)
    {
        //Name of button on hover
        $this->name = _('Vystavenie dokladov');

        //Button classes
        $this->class = 'btn-default';

        //Button Icon
        $this->icon = 'fa-file-pdf-o';

        //Allow button only when invoices are created
        $this->active = OrderService::hasInvoices();

        // Button and action support
        $this->type = 'both';
    }

    /*
     * Ask question with form before action
     */
    public function question($row)
    {
        return $this->title(_('Aký typ dokladu si prajete vygenerovať?'))
                    ->component('GenerateOrderInvoice', [
                        'invoice_types' => config('invoices.invoice_types', []),
                    ])
                    ->type('default');
    }

    /**
     * Generate invoice
     *
     * @param  mixed $row
     * @return void
     */
    public function generate($row)
    {
        if ( array_key_exists(request('invoice_type'), config('invoices.invoice_types', [])) === false ) {
            return $this->error(_('Nevybrali ste typ dokladu.'));
        }

        if ( $row->items->count() == 0 ) {
            return $this->error(sprintf(_('Objednávka č. %s neobsahuje žiadne položky k vygenerovaniu dokladu.'), $row->number));
        }

        $invoice = $row->makeInvoice('invoice');

        //Generate PDF
        if ( !($pdf = $invoice->getPdf()) ){
            return $this->error(sprintf(_('Doklad sa nepodarilo vygenerovať pre objednávku č. %s.'), $row->number));
        }

        return $pdf;
    }
}