<?php

namespace AdminEshop\Contracts\Payments;

use AdminEshop\Contracts\Payments\GPWebpay\Api as WebpayApi;
use AdminEshop\Contracts\Payments\GPWebpay\PaymentRequest;
use AdminEshop\Contracts\Payments\GPWebpay\PaymentResponse;
use AdminEshop\Contracts\Payments\GPWebpay\PaymentResponseException as WebpayPaymentResponseException;
use AdminEshop\Contracts\Payments\GPWebpay\Signer;
use AdminEshop\Contracts\Payments\GPWebpay\FinalizePaymentRequest;
use AdminEshop\Contracts\Payments\Exceptions\PaymentGateException;
use AdminEshop\Contracts\Payments\Exceptions\PaymentResponseException;
use AdminEshop\Contracts\Payments\PaymentHelper;
use Exception;
use Log;

class GPWebPayment extends PaymentHelper
{
    private $webpay;

    public function __construct($options = null)
    {
        parent::__construct($options);

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
                $payment->getKey(),
                $payment->price,
                PaymentRequest::EUR,
                0,
                $this->getResponseUrl('status'),
                $order->getKey(),
            );

            $request->setEmail($order->email);

            return $this->webpay->createPaymentRequestUrl($request);
        } catch (Exception $e) {
            throw new PaymentGateException($e->getMessage());
        }
    }

    public function getPaymentUrl($paymentResponse)
    {
        return $paymentResponse;
    }

    public function isPaid($id = null)
    {
        if ( !$this->webpay ) {
            throw new Exception('Webpay instance has not been found.');
        }

        $response = new PaymentResponse(
            request('OPERATION'),
            request('ORDERNUMBER'),
            request('MERORDERNUM') ?: request('ORDERNUMBER'),
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
        }

        // Digest is not correct.
        catch (Exception $e) {
            throw new PaymentResponseException($e->getMessage());
        }
    }
}

?>