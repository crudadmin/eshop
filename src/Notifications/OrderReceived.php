<?php

namespace AdminEshop\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Store;

class OrderReceived extends Notification
{
    use Queueable;

    private $order;

    private $delivery;

    private $payment_method;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($order, $delivery, $payment_method)
    {
        $this->order = $order;
        $this->delivery = $delivery;
        $this->payment_method = $payment_method;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
                    ->markdown('admineshop::mail.order.received', [
                        'order' => $this->order,
                        'delivery' => $this->delivery,
                        'payment_method' => $this->payment_method,
                    ])
                    ->subject(_('Objednávka č. ') . $this->order->number);

        //Attach order pdf
        $mail->attach($this->order->pdf->path, [
            'as' => 'objednavka-'.$this->order->number.'.pdf',
        ]);

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
