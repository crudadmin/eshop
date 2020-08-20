<?php

namespace AdminEshop\Contracts\Order\Validation;

use AdminEshop\Contracts\Order\Validation\Validation;
use Cart;

class StockValidator extends Validator
{
    /*
     * Pass validation
     */
    public function pass()
    {
        return $this->checkProductsAvaiability();
    }

    /**
     * Check avaiability of products in cart
     *
     * @return bool
     */
    public function checkProductsAvaiability()
    {
        $cart = Cart::all();

        foreach ($cart as $item) {
            $identifier = $item->getIdentifierClass();

            //If identifier has method for quantity check
            if (
                method_exists($identifier, 'hasQuantityOnStock')
                && $identifier->hasQuantityOnStock($item) === false
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns validation message
     *
     * @return  message
     */
    public function getMessage()
    {
        return _('Váš košík obsahuje produkty s nedostatočným množstvom na sklade. Prosíme, prekontrolujte obsah Vášho košíku.');
    }
}

?>