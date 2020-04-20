<?php

namespace AdminEshop\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Gogol\Invoices\Model\Invoice;

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
    public function __construct($order, Invoice $invoice = null)
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
        $mail = (new MailMessage)
                    ->subject(_('Potvrdenie platby k objednávke č. '). $this->order->number)
                    ->greeting('Dobrý deň, '. $this->order->username)
                    ->line(_('Vaša objednávka bola úspešne dokončená a zaplatená. Ďakujeme!'));

        if ( $this->invoice ) {
            $pdf = $this->invoice->getPdf();

            $mail->action(_('Stiahnuť doklad'), $pdf->url)
                 ->attach($pdf->path, [
                     'as' => $pdf->filename
                 ]);
        }

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