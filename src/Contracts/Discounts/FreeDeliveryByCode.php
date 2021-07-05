<?php

namespace AdminEshop\Contracts\Discounts;

use AdminEshop\Contracts\Discounts\Discount;
use AdminEshop\Contracts\Discounts\Discountable;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Orders\Order;
use OrderService;
use Store;

class FreeDeliveryByCode extends Discount implements Discountable
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
    public $canApplyOutsideCart = false;

    /*
     * We does not want cache discount codes, because they may be changed in order
     */
    public $cachableResponse = false;

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
        $codes = OrderService::getDiscountCodeDiscount()->getDiscountCodes();

        foreach ($codes as $code) {
            if ( $isActive = $this->hasCodeFreeDelivery($code) ){
                return $isActive;
            }
        }

        return false;
    }

    /*
     * Check if is discount active in administration
     */
    public function isActiveInAdmin(Order $order)
    {
        //Get discount code in order, if exists..
        foreach ($order->discountCodes as $code) {
            if ( $hasDiscount = $this->hasCodeFreeDelivery($order->discountCode) ){
                return $hasDiscount;
            }
        }

        return false;
    }

    /**
     * Check if code has free delivery
     *
     * @param  AdminEshop\Contracts\Discounts\DiscountCode  $code
     * @return  bool
     */
    private function hasCodeFreeDelivery($code)
    {
        if ( ! $code || $code->free_delivery != true ) {
            return false;
        }

        return $code;
    }

    /**
     * Boot discount parameters after isActive check
     *
     * @param  mixed  $code
     * @return void
     */
    public function boot($code)
    {
        $this->operator = '*';

        $this->value = 0;
    }
}

?>