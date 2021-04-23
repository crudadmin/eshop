<?php

namespace AdminEshop\Contracts\Order\Validation;

use AdminEshop\Contracts\Order\Validation\Validation;
use Cart;

class CartItemsValidator extends Validator
{
    /*
     * Pass validation
     */
    public function pass()
    {
        return $this->checkItemsCount();
    }

    /**
     * Check avaiability of products in cart
     *
     * @return bool
     */
    public function checkItemsCount()
    {
        return Cart::all()->count() > 0;
    }

    /**
     * Returns validation message
     *
     * @return  message
     */
    public function getMessage()
    {
        return _('Váš nákupný košík je prázdny.');
    }
}

?>