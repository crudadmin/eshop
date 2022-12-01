<?php

namespace AdminEshop\Mail;

use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Models\Orders\Order;
use Cart;
use Discounts;
use Gogol\Invoices\Model\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Localization;

class OrderStatus extends Mailable
{
    use Queueable, SerializesModels;

    private $order;
    private $status;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order, $status = null)
    {
        $this->order = $order;
        $this->status = $status ?: $order->status;

        //Boot website localization for templates, if is not booted yet.
        Localization::boot();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this
            ->markdown('admineshop::mail.order.status', [
                'order' => $this->order,
            ])->subject(
                sprintf(_('Zmena stavu objednávky č. %s na %s'), $this->order->number, $this->order->status->name)
            );

        if ( $this->status && $this->status->email_invoice ){
            $this->order->addInvoiceToStatusMail($mail);
        }

        return $mail;
    }
}
