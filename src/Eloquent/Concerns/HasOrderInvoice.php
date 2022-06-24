<?php

namespace AdminEshop\Eloquent\Concerns;

use OrderService;

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
            'payment_date' => $this->created_at->addDays(getInvoiceSettings()->payment_term),
            'country' => 'sk',
        ]);
    }

    public function makeInvoice($type = null)
    {
        $data = $this->getInvoiceData($type);

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

        return $invoice;
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

        return ($invoice = $this->invoice->first())
                ? $invoice->getPdf()->url
                : null;
    }
}