<?php

namespace AdminEshop\Contracts\Payments\Exceptions;

use AdminEshop\Contracts\Order\Exceptions\OrderException;

class PaymentResponseException extends OrderException
{
    public $code = 'PAYMENT_UNVERIFIED';
}