<?php

namespace AdminEshop\Admin\Rules;

use Admin;
use AdminEshop\Events\OrderStatusChange;
use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;
use Ajax;

class OnOrderStatusChange extends AdminRule
{
    public $frontend = true;

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

        $order->log()->create([
            'type' => 'info',
            'code' => 'status',
            'message' => $status->name,
        ]);

        event(new OrderStatusChange($order, $status));
    }
}