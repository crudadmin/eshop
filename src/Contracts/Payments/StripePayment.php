<?php

namespace AdminEshop\Contracts\Payments;

use AdminEshop\Contracts\Payments\Exceptions\PaymentGateException;
use AdminEshop\Contracts\Payments\Exceptions\PaymentResponseException;
use AdminEshop\Contracts\Payments\PaymentHelper;
use Exception;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;

class StripePayment extends PaymentHelper
{
    protected $client;

    public function __construct($options = null)
    {
        parent::__construct(
            array_merge($options ?: [], config('stripe'))
        );

        if ( !$this->getOption('api_key') ) {
            abort(500, 'Stripe configuration does not exists');
        }

        $this->client = new StripeClient(
            $this->getOption('api_key')
        );
    }

    public function getPaymentResponse()
    {
        $order = $this->getOrder();

        $data = [
            'mode' => 'payment',
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Order n. '.$order->number,
                        ],
                        'unit_amount' => round($order->price_vat * 100),
                    ],
                    'description' => $order->items->map(function($item){
                        return $item->quantity.'x - '.$item->getProductName();
                    })->join('... '),
                    'quantity' => 1,
                ],
            ],
            'customer_email' => $order->email,
            'success_url' => $this->getResponseUrl('status'),
            'cancel_url' => $this->getResponseUrl('status'),
            'payment_intent_data' => [
                'metadata' => [
                    'order_number' => $order->number,
                ],
            ],
        ];

        if ( $types = $this->getOption('payment_method_types') ){
            $data['payment_method_types'] = array_wrap($types);
        }

        try {
            $session = $this->client->checkout->sessions->create($data);

            $this->setPaymentId(
                $session->id,
                [
                    'data' => [
                        'intent_id' => $session->payment_intent,
                    ]
                ]
            );

            return $session->url;
        } catch (InvalidRequestException $e){
            throw new PaymentGateException($e->getRequestId(), null, $e->getHttpBody());
        } catch (Exception $e){
            throw new PaymentGateException($e->getMessage());
        }

        return $response;
    }

    public function isPaid($id = null)
    {
        $session = $this->client->checkout->sessions->retrieve($id);

        if ( $session->status == 'complete' ){
            return true;
        }

        throw new PaymentResponseException(
            'Payment is not paid.', null, $session
        );
    }
}

?>