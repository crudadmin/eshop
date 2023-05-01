<?php

namespace AdminEshop\Models\Orders;

use AdminPayments\Contracts\Concerns\Orderable;
use AdminPayments\Models\Payments\Payment as BasePayment;

class Payment extends BasePayment
{
    public function mutateFields($fields)
    {
        parent::mutateFields($fields);

        $fields->pushBefore([
            'order' => 'name:Objednavka|belongsTo:orders,name',
        ]);
    }

    public function getOrder() : Orderable
    {
        return $this->order;
    }
}