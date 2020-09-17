<?php

namespace AdminEshop\Mail;

use AdminEshop\Models\Orders\Order;
use Gogol\Invoices\Model\Invoice;
use Cart;
use Discounts;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderReceived extends Mailable
{
    use Queueable, SerializesModels;

    private $order;

    private $cartItems;

    private $cartSummary;

    private $discounts;

    private $owner = false;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order, $message = null, Invoice $invoice = null)
    {
        $this->order = $order;

        $this->message = $message;

        $this->invoice = $invoice;

        $this->cartItems = Cart::allWithMutators();

        $this->cartSummary = Cart::all()->getSummary(true);

        $this->discounts = Discounts::getDiscounts();
    }

    public function setOwner($state)
    {
        $this->owner = $state;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->markdown('admineshop::mail.order.received', [
                        'message' => $this->message,
                        'order' => $this->order,
                        'delivery' => $this->order->delivery,
                        'location' => $this->order->delivery_location,
                        'payment_method' => $this->order->payment_method,
                        'items' => $this->cartItems,
                        'summary' => $this->cartSummary,
                        'discounts' => $this->discounts,
                        'owner' => $this->owner,
                        'showNoVat' => config('admineshop.mail.show_no_vat', false)
                    ])
                    ->subject(_('Objednávka č. ') . $this->order->number);

        //Attach order pdf
        if ( $invoice = $this->invoice ) {
            $mail->attach($invoice->getPdf()->path, [
                'as' => 'objednavka-'.$invoice->number.'.pdf',
            ]);
        }

        return $mail;

    }
}
