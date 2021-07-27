<?php

namespace AdminEshop\Controllers\Payments;

use Admin;
use AdminEshop\Contracts\Payments\Concerns\PaymentErrorCodes;
use AdminEshop\Contracts\Payments\PaymentVerifier;
use AdminEshop\Models\Orders\Order;
use AdminEshop\Models\Orders\Payment;
use Admin\Controllers\Controller;
use Illuminate\Http\Request;
use OrderService;

class PaymentController extends Controller
{
    public function paymentStatus(Payment $payment, $type, $hash)
    {
        $order = $payment->order;

        $paymentClass = OrderService::setOrder($order)
                                    ->getPaymentProvider($payment->payment_method_id)
                                    ->setPayment($payment);

        //Check if is payment hash correct hash and ids
        if ( $hash != $paymentClass->getOrderHash($type) ) {
            abort(401);
        }

        $paymentVerifier = new PaymentVerifier($paymentClass, $payment);

        $response = $paymentVerifier->verifyPaidStatus(function(){
            OrderService::orderPaid();

            return redirect(OrderService::onPaymentSuccess());
        }, function($errorCode, $e) use ($order) {
            $order->log()->create([
                'type' => 'error',
                'code' => $errorCode,
                'log' => $e->getMessage(),
            ]);

            return redirect(OrderService::onPaymentError($errorCode));
        });

        //HTTP payment notification. 200 HTTP code
        if ( in_array($type, ['notification']) ){
            return 'ok';
        }

        return $response;
    }

    public function postPayment($order, $hash)
    {
        $order = Admin::getModel('Order')->findOrFail($order);

        $type = 'postpayment';

        OrderService::setOrder($order);

        //Check if is payment hash correct hash and ids
        if ( $hash != $order->makePaymentHash($type) ) {
            abort(401);
        }

        //Order has been paid already
        if ( $order->paid_at ) {
            return redirect(OrderService::onPaymentError(PaymentErrorCodes::CODE_PAID));
        }

        //If payment url could not be generated successfully
        if ( !($paymentUrl = $order->getPaymentUrl($order->payment_method_id)) ) {
            $paymentUrl = OrderService::onPaymentError(PaymentErrorCodes::CODE_PAID);
        }

        return redirect($paymentUrl);
    }
}