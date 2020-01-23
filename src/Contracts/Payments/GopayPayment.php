<?php

namespace AdminEshop\Contracts\Payments;

use AdminEshop\Contracts\Payments\PaymentHelper;
use Gopay;

class GopayPayment extends PaymentHelper
{
    private $gopay;

    public function __construct()
    {
        if ( !is_array($config = config('gopay')) ) {
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

    public function getResponseUrl($type)
    {
        return action('\AdminEshop\Controllers\Payments\GopayController@paymentStatus', [
            $this->getPayment()->getKey(),
            $type,
            $this->getOrderHash($type),
        ]);
    }

    private function getDefaultPayment()
    {
        return 'PAYMENT_CARD';
    }

    private function getItems()
    {
        $items = [];

        $order = $this->getOrder();

        foreach ($order->items as $item)
        {
            $items[] = [
                "name" => $item->productName,
                "count" => $item->quantity,
                "amount" => round($item->price_tax * $item->quantity * 100),
            ];
        }

        if ( $order->delivery ) {
            $items[] = [
                "name" => $order->delivery->name,
                "count" => 1,
                "amount" => round($order->deliveryPriceWithTax * 100),
            ];
        }

        if ( $order->payment_method ) {
            $items[] = [
                "name" => $order->payment_method->name,
                "count" => 1,
                "amount" => round($order->paymentMethodPriceWithTax * 100),
            ];
        }

        return $items;
    }

    public function getPaymentUrl()
    {
        $order = $this->getOrder();

        $response = $this->gopay->createPayment([
            "payer" => [
                "default_payment_instrument" => $this->getDefaultPayment(),
                "allowed_payment_instruments" => ["PAYMENT_CARD", "PAYPAL"],
            ],
            "amount" => round($order->price_tax * 100),
            "currency" => "EUR",
            "order_number" => $order->getKey(),
            "order_description" => sprintf(_('Platba %s'), env('APP_NAME')),
            "items" => $this->getItems(),
            "callback" => [
                "return_url" => $this->getResponseUrl('status'),
                "notification_url" => $this->getResponseUrl('notification')
            ],
            "lang" => "sk"
        ]);

        //Ak je vykonana poziadavka v poriadku
        if ($response->hasSucceed()) {
            return $response->json['gw_url'];
        } else {
            Log::error(json_encode($response->json));

            return false;
        }
    }

    public function isPaid($id = null)
    {
        if ( !($id = ($id ?: request('id'))) ) {
            return false;
        }

        $status = $this->gopay->getStatus($id);

        if ( $status->json['state'] == 'PAID' ) {
            return true;
        }

        return false;
    }
}

?>