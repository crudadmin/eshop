<?php

namespace AdminEshop\Eloquent\Concerns;

use Store;
use OrderService;

trait HasOrderEmails
{
    public function getStoreEmailReceivers()
    {
        if ( !($email = Store::getSettings()->email) ) {
            return;
        }

        return $email;
    }

    public function getClientEmailMessage()
    {
        if ( $this->status && $text = $this->status->parseOrderText('email_content', $this) ) {
            return $this->status->parseOrderText('email_content', $this);
        }

        return sprintf(_('Vaša objednávka č. %s zo dňa %s bola úspešne prijatá.'), $this->number, $this->created_at->format('d.m.Y'));
    }

    public function getStoreEmailMessage()
    {
        return sprintf(_('Gratulujeme! Obržali ste objednávku č. %s.'), $this->number);
    }

    public function getClientEmailSubject()
    {
        return _('Objednávka č. ') . $this->number;
    }

    public function getStoreEmailSubject()
    {
        return _('Objednávka č. ') . $this->number;
    }

    public function addInvoiceToStatusMail($mail)
    {
        if ( OrderService::hasInvoices() == false ){
            return;
        }

        $invoice = $this->invoices()->get()->sortByDesc(function($invoice){
            $sortBy = ['proform', 'invoice', 'return'];

            return array_search($invoice->type, $sortBy);
        })->first();

        $filename = sprintf(_('objednavka-%s'), $invoice->number).'.pdf';

        $mail->attachData($invoice->getPdf()->get(), $filename);
    }

    public function onOrderReceivedBuild($mail)
    {

    }
}