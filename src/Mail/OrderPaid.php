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

class OrderPaid extends Mailable
{
    use Queueable, SerializesModels;

    private $order;
    private $invoice;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order, Invoice $invoice = null)
    {
        $this->order = $order;
        $this->invoice = $invoice;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this
                ->subject(_('Potvrdenie platby k objednávke č. '). $this->order->number)
                ->markdown('admineshop::mail.order.paid', [
                    'message' => _('Vaša objednávka bola úspešne dokončená a zaplatená. Ďakujeme!'),
                    'order' => $this->order,
                    'invoice' => $this->invoice,
                ]);

        if ( $this->invoice && $pdf = $this->invoice->getPdf() ) {
            $mail->attach($pdf->path, [
                 'as' => $pdf->filename
            ]);
        }

        return $mail;

    }
}
