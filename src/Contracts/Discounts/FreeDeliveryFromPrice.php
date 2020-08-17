<?php

namespace AdminEshop\Contracts\Discounts;

use AdminEshop\Contracts\Discounts\Discount;
use AdminEshop\Contracts\Discounts\Discountable;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Orders\Order;
use Store;

class FreeDeliveryFromPrice extends Discount implements Discountable
{
    /**
     * Discount can be applied on those models
     *
     * @var  array
     */
    public $applyOnModels = [
        Delivery::class
    ];

    /**
     * Free delivery discount can't be applied outside cart
     *
     * @var  bool
     */
    public $canApplyOutsideCart = true;

    /**
     * Can be this discount shown in email?
     *
     * @return  bool
     */
    public function canShowInEmail()
    {
        return false;
    }

    /*
     * Discount name
     */
    public function getName()
    {
        return __('Doprava zdarma');
    }

    /*
     * Check if is discount active
     */
    public function isActive()
    {
        return true;
    }

    /*
     * Check if is discount active in administration
     */
    public function isActiveInAdmin(Order $order)
    {
        return true;
    }

    /**
     * Boot discount parameters after isActive check
     *
     * @param  mixed  $code
     * @return void
     */
    public function boot($code)
    {
        $this->operator = 'abs';

        $this->value = function($item){
            $summaryWithVat = @$this->getCartSummary()['priceWithVat'] ?: 0;

            return $summaryWithVat >= $item->free_from ? 0 : null;
        };
    }
}

?>