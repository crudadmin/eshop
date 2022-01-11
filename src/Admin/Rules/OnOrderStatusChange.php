<?php

namespace AdminEshop\Admin\Rules;

use Admin;
use AdminEshop\Mail\OrderStatus;
use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;
use Ajax;
use Illuminate\Support\Facades\Mail;
use Store;
use Exception;

class OnOrderStatusChange extends AdminRule
{
    /*
     * Firing callback on create row
     */
    public function creating(AdminModel $row)
    {
        $this->setStatusChange($row);
    }

    /*
     * Firing callback on update row
     */
    public function updating(AdminModel $row)
    {
        $this->setStatusChange($row);
    }

    /*
     * On delete product from admin, add goods back to stock
     */
    public function setStatusChange($order)
    {
        if ( $order->status_id == $order->getOriginal('status_id') ){
            return;
        }

        if ( !($status = $order->status) ){
            return;
        }

        if ( $status->email_send === true ){
            try {
                // dd($order->status->name, $order->status_id, $order->getOriginal('status_id'));
                Mail::to($order->email)->send(new OrderStatus($order));

                $order->log()->create([
                    'type' => 'info',
                    'message' => 'Email o zmene stavu objednávky "'.$order->status->name.'" bol odoslaný.',
                ]);
            } catch (Exception $e){
                $order->log()->create([
                    'type' => 'error',
                    'message' => 'Email o zmene stavu objednávky nebol odoslaný.',
                ]);
            }
        }
    }
}