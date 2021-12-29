<?php

namespace AdminEshop\Notifications;

use AdminEshop\Models\Products\ProductsNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductAvailableNotification extends Notification
{
    use Queueable;

    private $notification;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(ProductsNotification $notification)
    {
        $this->notification = $notification;
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
        $notification = $this->notification;

        if ( method_exists($notification, 'getAvaiabilityMail') ){
            return $notification->getAvaiabilityMail($notifiable);
        }

        $productName = $notification->variant?->name ?: $notification?->product->name;

        return (new MailMessage)
                    ->subject(sprintf(_('Skladom! - %s'), $productName))
                    ->line(sprintf(_('Produkt %s sme práve naskladnili.'), $productName))
                    ->action('Nakupovať', $notification->getProductUrl());
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
