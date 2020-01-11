<?php

namespace AdminEshop\Contracts;

use Admin;
use AdminEshop\Contracts\Discounts\DiscountCode;

class StoreDiscounts
{
    /*
     * All registered discounts applied on basket items and whole store
     */
    private $discounts = [
        DiscountCode::class,
    ];

    /**
     * Add discount class
     *
     * @param  string  $discountClass
     */
    public function addDiscounts($discountClass)
    {
        $this->discounts[] = $discountClass;
    }

    /*
     * Get all registered discounts in basket
     */
    public function getDiscounts()
    {
        $discounts = Admin::cache('store_discounts', function(){
            return array_map(function($className){
                return new $className;
            }, $this->discounts);
        });

        //Returns only active discounts
        return array_filter($discounts, function($discount){
            return $discount->isActive();
        });
    }


    /*
     * Register all discounts into product
     */
    public function applyDiscountsOnBasketItem($item)
    {
        $discounts = $this->getDiscounts();

        foreach ($discounts as $discount) {
            $discount->boot();

            //If discount is allowed for basket items
            if ( $discount->canApplyOnProductInBasket($item) === true ) {
                ($item->product ?: $item->product)->addDiscount(
                    $discount->getDiscountName(),
                    $discount->operator,
                    $discount->value
                );
            }
        }

        return $item;
    }

}

?>