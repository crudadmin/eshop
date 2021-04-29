<?php

namespace AdminEshop\contracts\Order\Concerns;

use Admin;
use OrderService;

trait HasOrderProcess
{
    public function validateOrder($mutators = null)
    {
        $row = Admin::getModel('Order')->orderValidator()->validate()->getData();

        //Remove uneccessary delivery and company info from order
        OrderService::setRequestData($row)->storeIntoSession();

        //Checks products avaiability. Some products may be sold already,
        //so we need throw an error.
        if ( OrderService::passesValidation($mutators) === false ) {
            return OrderService::errorResponse();
        }
    }
}