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
    public function __construct(Order $order, CartCollection $items = null, $message = null, Invoice $invoice = null)
    {
        $this->order = $order;

        $this->message = $message;

        $this->invoice = $invoice;

        //Summary must be before additional items are added into list. Because price of this items will be sumed 2 times.
        //Becasuse true parameter in getSummary indicates that we should sum all available items.
        $this->cartSummary = $items->getSummary(true);

        $this->cartItems = $items ? Cart::addItemsFromMutators($items, 'addHiddenCartItems') : null;

        $this->discounts = Discounts::getDiscounts();
    }

    public function setOwner($state)
    {
        $this->owner = $state;

        return $this;
    }

    private function getAvailableAdditionalFields()
    {
        $additionalFields = config('admineshop.cart.order.additional_email_fields', []);

        $fields = [];
        foreach ($additionalFields as $fieldKey) {
            if ( $this->order->getField($fieldKey) && !is_null($this->order->{$fieldKey}) ) {
                $fields[$fieldKey] = $this->order->getField($fieldKey);
            }
        }

        return $fields;
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
                        'showNoVat' => config('admineshop.mail.show_no_vat', false),
                        'existingAdditionalFields' => $this->getAvailableAdditionalFields(),
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
