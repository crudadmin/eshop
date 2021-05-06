<?php

namespace AdminEshop\Contracts\Payments;

use AdminEshop\Contracts\Payments\Exceptions\PaymentGateException;
use AdminEshop\Contracts\Payments\Exceptions\PaymentResponseException;
use AdminEshop\Contracts\Payments\PaymentHelper;
use Gopay;
use Log;

class GopayPayment extends PaymentHelper
{
    private $gopay;

    public function __construct()
    {
        if ( !is_array($config = config('gopay')) ) {
            abort(500, 'Gopay configuration does not exists');

            return;
        }

        $this->gopay = GoPay\Api::payments([
            'goid' => $config['goid'],
            'clientId' => $config['clientId'],
            'clientSecret' => $config['clientSecret'],
            'isProductionMode' => $config['production'],
            'scope' => \GoPay\Definition\TokenScope::ALL,
            'language' => \GoPay\Definition\Language::SLOVAK,
        ]);
    }

    private function getDefaultPayment()
    {
        $payment = $this->getPayment();

        $defaultPayment = env('GOPAY_DEFAULT_PAYMENT', 'PAYMENT_CARD');

        //Set default payment by card, if limit is not over
        if ( $payment->price <= $this->getPaymentLimits($defaultPayment) ) {
            return $defaultPayment;
        }
    }

    private function getAllowedPayments()
    {
        return array_values(array_filter([
            $this->isPaymentInLimit('PAYMENT_CARD') ? "PAYMENT_CARD" : null,
            $this->isPaymentInLimit('PAYPAL') ? "PAYPAL" : null,
            $this->isPaymentInLimit('BANK_ACCOUNT') ? "BANK_ACCOUNT" : null,
        ]));
    }

    private function getPaymentLimits($key = null)
    {
        $limits = [
            'PAYMENT_CARD' => env('GOPAY_PAYMENT_CARD_LIMIT', 2000),
            'BANK_ACCOUNT' => env('GOPAY_BANK_ACCOUNT_LIMIT', 4000),
            'PAYPAL' => env('GOPAY_PAYPAL_LIMIT', 1000),
            'BITCOIN' => env('GOPAY_BITCOIN_LIMIT', 1000),
            'PRSMS' => env('GOPAY_PRSMS_LIMIT', 20),
        ];

        return $key === null ? $limits : @$limits[$key];
    }

    public function isPaymentInLimit($key)
    {
        $payment = $this->getPayment();

        if ( $payment->price <= $this->getPaymentLimits($key) ) {
            return $key;
        }
    }

    private function getItems()
    {
        $items = [];

        $order = $this->getOrder();

        foreach ($order->items as $item)
        {
            $items[] = [
                "name" => $item->getProductName(),
                "count" => $item->quantity,
                "amount" => round($item->price_vat * $item->quantity * 100),
            ];
        }

        if ( $order->delivery ) {
            $items[] = [
                "name" => $order->delivery->name,
                "count" => 1,
                "amount" => round($order->deliveryPriceWithVat * 100),
            ];
        }

        if ( $order->payment_method ) {
            $items[] = [
                "name" => $order->payment_method->name,
                "count" => 1,
                "amount" => round($order->paymentMethodPriceWithVat * 100),
            ];
        }

        return $items;
    }

    private function getPayer()
    {
        $payment = $this->getPayment();
        $order = $this->getOrder();

        if ( count($this->getAllowedPayments()) == 0 ){
            return false;
        }

        return array_filter([
            "default_payment_instrument" => $this->getDefaultPayment(),
            "allowed_payment_instruments" => $this->getAllowedPayments(),
            "contact" => array_filter([
                "first_name" => $order->firstname,
                "last_name" => $order->lastname,
                "email" => $order->email,
                "phone_number" => $order->phone,
            ]),
        ]);
    }

    public function getPaymentResponse()
    {
        if ( !$this->gopay ){
            return false;
        }

        $order = $this->getOrder();

        $payment = $this->getPayment();

        //If payer data are not available
        if ( !($payer = $this->getPayer()) ){
            return false;
        }

        $response = $this->gopay->createPayment([
            "payer" => $payer,
            "amount" => round($payment->price * 100),
            "currency" => "EUR",
            "order_number" => $payment->getKey(),
            "order_description" => sprintf(_('Platba %s'), env('APP_NAME')),
            "items" => $this->getItems(),
            "callback" => [
                "return_url" => $this->getResponseUrl('status'),
                "notification_url" => $this->getResponseUrl('notification')
            ],
            "lang" => app()->getLocale()
        ]);

        //Ak je vykonana poziadavka v poriadku
        if ($response->hasSucceed()) {
            return $response->json['gw_url'];
        } else {
            throw new PaymentGateException(
                json_encode($response->json, JSON_PRETTY_PRINT)
            );

            return false;
        }
    }

    public function getPaymentUrl($paymentResponse)
    {
        return $paymentResponse;
    }

    /**
     * In email post payment redirect we want redirect on same link as first payment link
     *
     * @param  string  $paymentResponse
     *
     * @return  string
     */
    public function getPostPaymentUrl($paymentResponse)
    {
        return $paymentResponse;
    }

    public function getPaymentData($paymentResponse)
    {
        return [
            'url' => $paymentResponse,
        ];
    }

    public function isPaid($id = null)
    {
        if ( !$this->gopay ) {
            throw new Exception('Gopay instance has not been found.');
        }

        if ( !($id = ($id ?: request('id'))) ) {
            throw new Exception('Gopay ID is missing.');
        }

        $status = $this->gopay->getStatus($id);

        if ( $status->json['state'] == 'PAID' ) {
            return true;
        }

        throw new PaymentResponseException('Payment not verified.');
    }
}

?>