<?php

namespace AdminEshop\Contracts\Payments;

use AdamStipak\Webpay\Api as WebpayApi;
use AdamStipak\Webpay\PaymentRequest;
use AdamStipak\Webpay\PaymentResponse;
use AdamStipak\Webpay\PaymentResponseException as WebpayPaymentResponseException;
use AdamStipak\Webpay\Signer;
use AdminEshop\Contracts\Payments\Exceptions\PaymentGateException;
use AdminEshop\Contracts\Payments\Exceptions\PaymentResponseException;
use AdminEshop\Contracts\Payments\PaymentHelper;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use Log;

class GPWebPayment extends PaymentHelper
{
    private $webpay;

    public function __construct()
    {
        if ( !is_array($config = config('webpay')) ) {
            abort(500, 'Webpay configuration does not exists');

            return;
        }

        $signer = new Signer(
            base_path($config['priv_path']),      // Path of private key.
            $config['password'],                  // Password for private key.
            base_path($config['pub_path'])        // Path of public key.
        );

        $this->webpay = new WebpayApi(
            $config['merchant_id'],
            $config['production'] ? 'https://3dsecure.gpwebpay.com/pgw/order.do' : 'https://test.3dsecure.gpwebpay.com/pgw/order.do',
            $signer
        );
    }

    public function getPaymentResponse()
    {
        if ( !$this->webpay ){
            return false;
        }

        try {
            $order = $this->getOrder();

            $payment = $this->getPayment();

            $request = new PaymentRequest(
                $order->getKey(),
                $payment->price,
                PaymentRequest::EUR,
                0,
                $this->getResponseUrl('status'),
                $order->getKey()
            );

            $url = $this->webpay->createPaymentRequestUrl($request);
            $url = $this->getFinalPaymentUrl($url);

            return $url;
        } catch (Exception $e) {
            throw new PaymentGateException($e->getMessage());
        }
    }

    /*
     * We want send into email final payment redirect,
     * because first redirect wont work in email and will throw an error
     */
    private function getFinalPaymentUrl($url)
    {
        $client = new Client([
            'allow_redirects' => true
        ]);

        $redirects = [];

        $client->get($url, [
            'on_stats' => function (TransferStats $stats) use (&$redirects) {
                $redirects[] = (string)$stats->getEffectiveUri();
            },
        ]);

        return end($redirects);
    }

    public function getPaymentUrl($paymentResponse)
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
        if ( !$this->webpay ) {
            return false;
        }

        $response = new PaymentResponse(
            request('OPERATION'),
            request('ORDERNUMBER'),
            request('MEORDERNUM') ?: request('ORDERNUMBER'),
            request('PRCODE'),
            request('SRCODE'),
            request('RESULTTEXT'),
            request('DIGEST'),
            request('DIGEST1'),
        );

        //Successfull payment
        try {
            $this->webpay->verifyPaymentResponse($response);
        }

        //Payment is not successfull
        catch (WebpayPaymentResponseException $e) {
            throw new PaymentResponseException($e->getMessage());

            return false;
        }

        // Digest is not correct.
        catch (Exception $e) {
            throw new PaymentResponseException($e->getMessage());
        }
    }
}

?>