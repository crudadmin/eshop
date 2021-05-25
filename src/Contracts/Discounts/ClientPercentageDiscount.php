<?php

namespace AdminEshop\Contracts\Discounts;

use Admin;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Discounts\Discount;
use AdminEshop\Contracts\Discounts\Discountable;
use AdminEshop\Models\Orders\Order;
use Store;

class ClientPercentageDiscount extends Discount implements Discountable
{
    /*
     * We does not want cache this response
     */
    public $cachableResponse = false;

    /*
     * Discount name
     */
    public function getName()
    {
        return __('Klientska zľava');
    }

    /*
     * Check if is discount active
     */
    public function isActive()
    {
        return $this->getActiveDiscountLevel() ?: false;
    }

    /*
     * Check if is discount active
     */
    public function isActiveInAdmin($order)
    {
        return $this->getActiveDiscountLevel() ?: false;
    }

    public function boot($discountLevel)
    {
        $this->operator = '-%';

        $this->value = $this->getClient()->percentage_discount;
    }

    public function getActiveDiscountLevel($price = null)
    {
        return $this->getClient() && $this->getClient()->percentage_discount > 0;
    }
}

?>