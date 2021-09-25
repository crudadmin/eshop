<?php

namespace AdminEshop\Contracts\Payments\Exceptions;

use AdminEshop\Contracts\Order\Exceptions\OrderException;

class PaymentGateException extends OrderException
{
    public $code = 'PAYMENT_ERROR';
}