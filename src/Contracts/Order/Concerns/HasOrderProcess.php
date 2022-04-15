<?php

namespace AdminEshop\Contracts\Order\Concerns;

use Admin;
use Illuminate\Http\Request;

trait HasOrderProcess
{
    public function validateOrder($mutators = null, $fetchStoredClientData = false, $saveDataIntoSession = true, $submitOrder = false)
    {
        $request = $this->getPreparedOrderRequest($submitOrder, $fetchStoredClientData);

        $row = Admin::getModel('Order')->orderValidator($request)->validate()->getData();

        //Remove uneccessary delivery and company info from order
        $this->setRequestData($row, $submitOrder, $saveDataIntoSession);

        //Checks products avaiability. Some products may be sold already,
        //so we need throw an error.
        if ( $this->passesValidation($mutators) === false ) {
            return $this->errorResponse();
        }

        //We cant return anything here. because this method is used as
        //if ( ..->validateOrder(...) ) {}
    }

    public function processFinalOrderValidation($mutators = null)
    {
        $request = $this->getPreparedOrderRequest(true, true);

        Admin::getModel('Order')->orderValidator($request)->validate();

        return $this->validateOrder($mutators, true, false, true);
    }
}