<?php

namespace AdminEshop\Contracts\Discounts;

use Admin;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Discounts\Discount;
use AdminEshop\Contracts\Discounts\Discountable;
use AdminEshop\Models\Orders\Order;
use Store;

class ClientPercentage extends Discount implements Discountable
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
        return __('Zákaznícka zľava');
    }

    /*
     * Check if is discount active
     */
    public function isActive()
    {
        $discount = $this->getActiveDiscount();

        if ( !$discount || $discount <= 0 ){
            return false;
        }

        return $discount;
    }

    /*
     * Check if is discount active
     */
    public function isActiveInAdmin($order)
    {
        $discount = $this->getActiveDiscount();

        if ( !$discount || $discount <= 0 ){
            return false;
        }

        return $discount;
    }

    public function boot($discount)
    {
        $this->operator = '-%';

        $this->value = $discount;
    }

    private function getActiveDiscount($price = null)
    {
        if ( !$this->getClient() ) {
            return 0;
        }

        return $this->getClient()->percentage_discount;
    }
}

?>