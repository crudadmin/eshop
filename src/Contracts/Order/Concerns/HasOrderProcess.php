<?php

namespace AdminEshop\Contracts\Order\Concerns;

use Admin;

trait HasOrderProcess
{
    private function getValidatedOrderRow($fetchStoredClientData = false)
    {
        $request = request();

        if ( $fetchStoredClientData ){
            $clientData = $this->getClientDataMutator()->getClientData() ?: [];

            $request->merge($request->all() + $clientData);
        }

        $validator = Admin::getModel('Order')->orderValidator($request);

        return $validator->validate()->getData();
    }

    public function validateOrder($mutators = null, $fetchStoredClientData = false, $saveDataIntoSession = true, $submitOrder = false)
    {
        $row = $this->getValidatedOrderRow($fetchStoredClientData);

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
        $row = $this->getValidatedOrderRow(true);

        return $this->validateOrder($mutators, true, false, true);
    }
}