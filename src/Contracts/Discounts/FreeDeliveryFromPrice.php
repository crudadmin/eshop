<?php

namespace AdminEshop\Contracts\Discounts;

use AdminEshop\Contracts\Discounts\Discount;
use AdminEshop\Contracts\Discounts\Discountable;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Orders\Order;
use OrderService;

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
        return _('Doprava zdarma');
    }

    /*
     * Check if is discount active
     */
    public function isActive()
    {
        if ( !($delivery = OrderService::getDeliveryMutator()->getSelectedDelivery()) ) {
            return false;
        }

        return $this->getDeliveryDiscounts($delivery);
    }

    /*
     * Check if is discount active in administration
     */
    public function isActiveInAdmin(Order $order)
    {
        if ( !($delivery = $order->delivery) ) {
            return false;
        }

        return $this->getDeliveryDiscounts($delivery);
    }

    public function getDeliveryDiscounts($delivery = null)
    {
        if ( !$delivery || !$delivery->free_from || $delivery->free_from <= 0 ) {
            return false;
        }

        return $delivery->only('id', 'free_from', 'free_from_price');
    }

    /**
     * Boot discount parameters after isActive check
     *
     * @param  mixed  $code
     * @return void
     */
    public function boot($discountData)
    {
        $this->operator = 'abs';

        $this->value = function($item) use ($discountData) {
            // Other discounts should use fresh data.
            if ( ($discountData['id'] ?? null) !== $item->id ) {
                $discountData = $item->only('id', 'free_from', 'free_from_price');
            }

            $freeFrom = $discountData['free_from'] ?? null;
            $freeFromPrice = $discountData['free_from_price'] ?? null;

            //If free delivery from price is not defined
            if ( !$freeFrom ) {
                return;
            }

            $summaryWithVat = @$this->getCartSummary()['priceWithVat'] ?: 0;

            return $summaryWithVat >= $freeFrom ? $freeFromPrice : null;
        };
    }
}

?>