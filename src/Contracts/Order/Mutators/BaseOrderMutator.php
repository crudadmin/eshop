<?php

namespace AdminEshop\Contracts\Order\Mutators;

use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Contracts\Order\Validation\CartItemsValidator;
use AdminEshop\Contracts\Order\Validation\StockValidator;

class BaseOrderMutator extends Mutator
{
    /**
     * Register validator with this mutators
     *
     * @var  array
     */
    protected $validators = [
        CartItemsValidator::class,
        StockValidator::class,
    ];
}

?>