<?php

namespace AdminEshop\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class OrderPaid extends Notification
{
    use Queueable;

    private $order;
    private $invoice;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($order, $invoice)
    {
        $this->order = $order;
        $this->invoice = $invoice;
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
        $pdf = $this->invoice->getPdf();

        return (new MailMessage)
                    ->subject(_('Doklad k objednávke č. '). $this->order->number)
                    ->greeting('Dobrý deň, '. $this->order->username)
                    ->line(_('Vaša objednávka bola úspešne dokončená a zaplatená. Ďakujeme!'))
                    ->action(_('Stiahnuť doklad'), $pdf->url)
                    ->attach($pdf->path, [
                        'as' => $pdf->filename
                    ]);
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