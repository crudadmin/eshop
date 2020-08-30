<?php

namespace AdminEshop\contracts\Order\Concerns;

use Admin;
use AdminEshop\Requests\SubmitOrderRequest;
use OrderService;

trait HasOrderProcess
{
    private function getAdminValidator()
    {
        return Admin::getModel('Order')->validator()->use(
            config('admineshop.cart.order.validator', SubmitOrderRequest::class)
        );
    }

    public function validateOrder()
    {
        $row = $this->getAdminValidator()->validate()->getData();

        //Remove uneccessary delivery and company info from order
        OrderService::setRequestData($row)->storeIntoSession();

        //Checks products avaiability. Some products may be sold already,
        //so we need throw an error.
        if ( OrderService::passesValidation() === false ) {
            return OrderService::errorResponse();
        }
    }
}