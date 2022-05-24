<?php

namespace AdminEshop\Contracts\Payments\Stripe;

use AdminEshop\Models\Orders\Payment;
use OrderService;
use Exception;
use Stripe\Stripe;
use Log;

class StripeWebhooks
{
    public function setApiKey()
    {
        Stripe::setApiKey(config('stripe.api_key'));
    }

    public function getWebhookEvent()
    {
        $this->setApiKey();

        $endpointSecret = config('stripe.webhook_secret');

        $payload = @file_get_contents('php://input');
        $event = null;

        try {
            $event = \Stripe\Event::constructFrom(json_decode($payload, true));
        } catch(\UnexpectedValueException $e) {
            throw new Exception('Webhook error while parsing basic request.');
        } catch (\Throwable $e){
            throw new Exception($e);
        }

        if ($endpointSecret) {
            // Only verify the event if there is an endpoint secret defined
            // Otherwise use the basic decoded event
            $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'];
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload, $sigHeader, $endpointSecret
                );
            } catch(\Stripe\Exception\SignatureVerificationException $e) {
                // Invalid signature
                throw new Exception('Webhook error while validating signature.');
            }
        }

        return $event;
    }

    public function onWebhookEvent($event)
    {
        if ( config('logging.channels.stripe_webhooks') ) {
            Log::channel('stripe_webhooks')->info($event);
        }

        if ( $event->type == 'checkout.session.completed' ) {
            $session = $event->data->object;

            $payment = Payment::where('payment_id', $session->id)->first();

            OrderService::isPaymentPaid($payment, $payment->order);
        }
    }
}

?>