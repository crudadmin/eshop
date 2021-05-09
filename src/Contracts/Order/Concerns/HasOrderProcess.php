<?php

namespace AdminEshop\Contracts\Order\Concerns;

use Admin;
use OrderService;

trait HasOrderProcess
{
    private function getValidatedOrderRow($fetchStoredClientData = false)
    {
        $request = request();

        if ( $fetchStoredClientData ){
            $clientData = OrderService::getFromSession() ?: [];

            $request->merge($request->all() + $clientData);
        }

        $validator = Admin::getModel('Order')->orderValidator($request);

        return $validator->validate()->getData();
    }

    public function validateOrder($mutators = null, $fetchStoredClientData = false, $saveDataIntoSession = true, $submitOrder = false)
    {
        $row = $this->getValidatedOrderRow($fetchStoredClientData);

        //Remove uneccessary delivery and company info from order
        OrderService::setRequestData($row, $submitOrder);

        if ( $saveDataIntoSession === true ) {
            OrderService::storeIntoSession();
        }

        //Checks products avaiability. Some products may be sold already,
        //so we need throw an error.
        if ( OrderService::passesValidation($mutators) === false ) {
            return OrderService::errorResponse();
        }
    }

    public function processFinalOrderValidation($mutators = null)
    {
        $row = $this->getValidatedOrderRow(true);

        return $this->validateOrder($mutators, true, false, true);
    }
}