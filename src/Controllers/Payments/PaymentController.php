<?php

namespace AdminEshop\Controllers\Payments;

use Admin;
use AdminEshop\Contracts\Payments\Concerns\PaymentErrorCodes;
use AdminEshop\Contracts\Payments\Exceptions\PaymentResponseException;
use AdminEshop\Contracts\Payments\PaymentVerifier;
use AdminEshop\Models\Orders\Order;
use AdminEshop\Models\Orders\Payment;
use Admin\Controllers\Controller;
use Illuminate\Http\Request;
use OrderService;
use Exception;

class PaymentController extends Controller
{
    public function paymentStatus(Payment $payment, $type, $hash)
    {
        $order = $payment->order;

        $redirect = null;

        $paymentProvider = OrderService::setOrder($order)
                                    ->getPaymentProvider($payment->payment_method_id)
                                    ->setPayment($payment);

        //Check if is payment hash correct hash and ids
        if ( $hash != $paymentProvider->getOrderHash($type) ) {
            abort(401);
        }

        try {
            $paymentProvider->isPaid(
                $paymentProvider->getPaymentId()
            );

            //Custom paid callback. We also can overide default redirect
            if ( method_exists($paymentProvider, 'onPaid')){
                $redirect = $paymentProvider->onPaid($payment);
            }

            //Default paid callback
            else {
                //Update payment status
                $payment->update([ 'status' => 'paid' ]);

                OrderService::orderPaid();
            }

            //If redirect is not set yet
            if ( ! $redirect ){
                $redirect = redirect(OrderService::onPaymentSuccess());
            }
        } catch (Exception $e){
            if ( OrderService::isDebug() ){
                throw $e;
            }

            if ( $e instanceof PaymentResponseException ) {
                $errorCode = PaymentErrorCodes::CODE_PAYMENT_UNVERIFIED;
            } else {
                $errorCode = PaymentErrorCodes::CODE_ERROR;
            }

            $order->log()->create([
                'type' => 'error',
                'code' => $errorCode,
                'log' => $e->getMessage(),
            ]);

            $redirect = redirect(OrderService::onPaymentError($errorCode));
        }

        //Does not return redirect response on notification
        if ( in_array($type, ['notification']) ){
            return 'ok';
        }

        return $redirect;
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