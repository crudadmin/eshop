<?php

namespace AdminEshop\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderStoreReceived extends Mailable
{
    use Queueable, SerializesModels;

    private $order;

    private $delivery;

    private $payment_method;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($order, $delivery, $payment_method)
    {
        $this->order = $order;
        $this->delivery = $delivery;
        $this->payment_method = $payment_method;
    }

    /*
     * Return mutated subject
     */
    private function getSubject()
    {
        $subject = _('Objednávka č. ') . $this->order->number;

        if ( $this->order->note )
            $subject .= ' - P';

        return $subject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->markdown('admineshop::mail.order.store-received', [
                    'order' => $this->order,
                    'delivery' => $this->delivery,
                    'payment_method' => $this->payment_method,
                ])
                ->subject( $this->getSubject() )
                ->from($this->order->email, $this->order->username)
                ->replyTo( $this->order->email );

        //Attach order pdf
        // $mail->attach($this->order->pdf->path, [
        //     'as' => 'objednavka-'.$this->order->number.'.pdf',
        // ]);

        return $mail;

    }
}
