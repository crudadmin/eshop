<?php

namespace AdminEshop\Eloquent\Concerns;

use Gogol\Invoices\Model\Invoice;
use OrderService;
Use Exception;
use Log;

trait HasOrderInvoice
{
    public function getInvoiceData($type)
    {
        return array_merge($this->makeHidden([
            'number', 'number_prefix',
            'deleted_at', 'created_at', 'updated_at', 'client_id',
        ])->toArray(), [
            'order_id' => $this->getKey(),
            'company_name' => $this->company_name ?: $this->username,
            'city' => $this->city,
            'street' => $this->street,
            'zipcode' => $this->zipcode,

            'delivery_company_name' => $this->delivery_username ?: $this->company_name ?: $this->username,
            'delivery_city' => $this->delivery_city ?: $this->city,
            'delivery_street' => $this->delivery_street ?: $this->street,
            'delivery_zipcode' => $this->delivery_zipcode ?: $this->zipcode,
            'delivery_country_id' => $this->delivery_country_id ?: $this->country_id,

            'note' => $this->note,
            'price' => $this->price,
            'price_vat' => $this->price_vat,
            'payment_method_id' => $this->payment_method_id,
            'vs' => $this->number,
            'country' => 'sk',
        ]);
    }

    public function makeInvoice($type = null, $data = [])
    {
        if ( $this->hasInvoices() == false ){
            return;
        }

        try {
            $data = array_merge($this->getInvoiceData($type), $data);

            //If is creating invoice, and order has proform
            if (
                $type == 'invoice'
                && $proform = $this->invoices()->where('type', 'proform')->select(['id'])->first()
            ) {
                $data['proform_id'] = $proform->getKey();
            }

            //If invoice exists, regenerate it.
            if ( $invoice = $this->invoices()->where('type', $type)->first() ){
                //Delete invoice items for invoice regeneration
                $invoice->items()->forceDelete();

                $invoice->update($data);
            }

            //If invoice does not exists
            else {
                $invoice = invoice()->make($type, $data);

                $invoice->save();
            }

            $this->addMissingInvoiceOrderItems([], $invoice);

            //Change invoice payment status
            $this->setUnpaidInvoiceProformState($invoice);

            return $invoice;
        } catch (Exception $error){
            Log::error($error);

            $order->log()->create([
                'type' => 'error',
                'code' => 'INVOICE_ERROR',
                'log' => $error->getMessage()
            ]);

            //Debug
            if ( OrderService::isDebug() ) {
                throw $error;
            }
        }
    }

    private function setUnpaidInvoiceProformState($invoice)
    {
        //Set unpaid proform as paid
        if ( $invoice->type == 'invoice' && $invoice->paid_at && $invoice->proform && !$invoice->proform->paid_at ){
            $invoice->proform->update([
                'paid_at' => $invoice->paid_at,
            ]);
        }
    }

    public function addMissingInvoiceOrderItems($items, $invoice)
    {
        //Add order items
        foreach ($this->items as $item) {
            $invoice->items()->create([
                'name' => $item->invoiceItemName(),
                'quantity' => $item->quantity,
                'vat' => $item->vat,
                'price' => $item->price,
                'price_vat' => $item->price_vat,
                'identifier' => $item->identifier,
            ]);
        }

        //Add delivery item
        if ( $this->delivery ) {
            $invoice->items()->create([
                'name' => $this->delivery->name,
                'quantity' => 1,
                'vat' => $this->delivery_vat,
                'price' => $this->delivery_price,
                'price_vat' => $this->deliveryPriceWithVat,
                'identifier' => 'delivery',
            ]);
        }

        //Add payment method
        if ( $this->payment_method ) {
            $invoice->items()->create([
                'name' => $this->payment_method->name,
                'quantity' => 1,
                'vat' => $this->payment_method_vat,
                'price' => $this->payment_method_price,
                'price_vat' => $this->paymentMethodPriceWithVat,
                'identifier' => 'payment',
            ]);
        }
    }

    public function getInvoiceUrlAttribute()
    {
        if ( OrderService::hasInvoices() == false ){
            return;
        }

        return ($invoice = $this->invoices->first())
                ? $invoice->getPdf()->url
                : null;
    }
}