<?php

namespace AdminEshop\Controllers\Payments;

use AdminEshop\Contracts\Payments\Concerns\PaymentErrorCodes;
use AdminEshop\Contracts\Payments\Exceptions\PaymentResponseException;
use AdminEshop\Events\OrderPaid as OrderPaidEvent;
use AdminEshop\Mail\OrderPaid;
use AdminEshop\Models\Orders\Order;
use AdminEshop\Models\Orders\Payment;
use Admin\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Log;
use Mail;
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

        //Check payment
        try {
            //Check if order is paid or throw ErrorPaymentException
            $paymentClass->isPaid();

            //If order is not paid
            if ( ! $order->paid_at ) {
                event(new OrderPaidEvent($order));

                //Update order status paid
                $order->update([
                    'paid_at' => \Carbon\Carbon::now()
                ]);

                //Update payment status
                $payment->update([
                    'status' => 'paid',
                ]);

                //Generate invoice
                $invoice = OrderService::makeInvoice('invoice');

                //Send invoice email
                try {
                    Mail::to($order->email)->send(
                        new OrderPaid($order, $invoice)
                    );
                } catch (Exception $e){
                    Log::channel('store')->error($e);

                    $order->log()->create([
                        'type' => 'error',
                        'code' => 'email-payment-done-error',
                    ]);
                }
            }

            return redirect(OrderService::onPaymentSuccess());
        }

        catch (PaymentResponseException $e) {
            $order->log()->create([
                'type' => 'error',
                'code' => 'payment-status-error',
                'log' => $e->getMessage(),
            ]);

            return redirect(
                OrderService::onPaymentError(PaymentErrorCodes::CODE_PAYMENT_UNVERIFIED)
            );
        }

        catch (Exception $e) {
            Log::channel('store')->error($e);

            $order->log()->create([
                'type' => 'error',
                'code' => 'payment-status-unknown-error',
                'log' => $e->getMessage(),
            ]);

            return redirect(
                OrderService::onPaymentError(PaymentErrorCodes::CODE_ERROR)
            );
        }
    }

    public function postPayment(Order $order, $hash)
    {
        $type = 'postpayment';

        OrderService::setOrder($order);

        //Check if is payment hash correct hash and ids
        if ( $hash != $order->makePaymentHash($type) ) {
            abort(401);
        }

        //Order has been paid already
        if ( $order->paid_at ) {
            return redirect(
                OrderService::onPaymentError(
                    PaymentErrorCodes::CODE_PAID
                )
            );
        }

        $paymentUrl = $order->getPaymentUrl(
            $order->payment_method_id
        );

        //If payment url could not be generated successfully
        if ( !$paymentUrl ) {
            $paymentUrl = OrderService::onPaymentError(
                PaymentErrorCodes::CODE_PAID
            );
        }

        return redirect($paymentUrl);
    }
}