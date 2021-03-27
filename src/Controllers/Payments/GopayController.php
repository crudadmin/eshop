<?php

namespace AdminEshop\Controllers\Payments;

use AdminEshop\Models\Orders\Payment;
use AdminEshop\Notifications\OrderPaid;
use Admin\Controllers\Controller;
use Illuminate\Http\Request;
use OrderService;
use Exception;
use Log;

class GopayController extends Controller
{
    public function paymentStatus(Payment $payment, $type, $hash)
    {
        $paymentId = request('id');

        $order = $payment->order;

        $paymentClass = OrderService::setOrder($order)
                                    ->getPaymentClass($payment->payment_method_id);

        //Check if is payment hash correct hash and ids
        if ( $hash != $paymentClass->getOrderHash($type) ) {
            abort(500);
        }

        //Check payment
        if ( $paymentClass->isPaid() )
        {
            //If order is not paid
            if ( ! $order->paid_at )
            {
                //Update order status paid
                $order->update([
                    'paid_at' => \Carbon\Carbon::now()
                ]);

                //Generate invoice
                $invoice = OrderService::makeInvoice('invoice');

                //Send invoice email
                try {
                    $order->notify(
                        new OrderPaid($order, $invoice)
                    );
                } catch (Exception $e){
                    Log::error($e);

                    $order->log()->create([
                        'type' => 'error',
                        'code' => 'email-payment-done-error',
                    ]);
                }
            }

            return redirect(OrderService::onPaymentSuccess());
        } else {
            return redirect(OrderService::onPaymentError());
        }
    }
}