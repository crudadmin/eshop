<?php

namespace AdminEshop\Mail;

use AdminEshop\Models\Orders\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Cart;
use Discounts;

class OrderReceived extends Mailable
{
    use Queueable, SerializesModels;

    private $order;

    private $cartItems;

    private $cartSummary;

    private $discounts;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order, $message = null)
    {
        $this->order = $order;

        $this->message = $message;

        $this->cartItems = Cart::all();

        $this->cartSummary = $this->cartItems->getSummary(true);

        $this->discounts = Discounts::getDiscounts();
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
                        'payment_method' => $this->order->payment_method,
                        'items' => $this->cartItems,
                        'summary' => $this->cartSummary,
                        'discounts' => $this->discounts,
                    ])
                    ->subject(_('Objednávka č. ') . $this->order->number);

        //Attach order pdf
        // $mail->attach($this->order->pdf->path, [
        //     'as' => 'objednavka-'.$this->order->number.'.pdf',
        // ]);

        return $mail;

    }
}
