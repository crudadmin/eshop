<?php

namespace AdminEshop\Contracts\Discounts;

use AdminEshop\Contracts\Discounts\Discount;
use AdminEshop\Models\Delivery\Delivery;
use Store;

class FreeDelivery extends Discount
{
    /**
     * Discount can be applied on those models
     *
     * @var  array
     */
    public $applyOnModels = [ Delivery::class ];

    /**
     * Free delivery cant be applied outside cart
     *
     * @var  bool
     */
    public $canApplyOutsideCart = false;

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
        return DiscountCode::getDiscountCode();
    }

    /**
     * Boot discount parameters after isActive check
     *
     * @param  mixed  $code
     * @return void
     */
    public function boot($code)
    {
        if ( $code->free_delivery ) {
            $this->operator = '*';

            $this->value = 0;
        }
    }
}

?>