<?php

namespace AdminEshop\Contracts;

use AdminEshop\Contracts\Discounts\DiscountCode;
use Admin\Core\Contracts\DataStore;

class StoreDiscounts
{
    use DataStore;

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
    public function addDiscount($discountClass)
    {
        $this->discounts[] = $discountClass;
    }

    /**
     * Get all registered discounts in basket
     *
     * @param  array  $exceps
     * @return array
     */
    public function getDiscounts($exceps = [])
    {
        $discounts = $this->cache('store_discounts', function(){
            return array_map(function($className){
                return new $className;
            }, $this->discounts);
        });

        //Returns only active discounts
        return array_filter($discounts, function($discount) use ($exceps) {
            if ( in_array($discount->getDiscountName(), $exceps) || !($response = $discount->isActive()) ) {
                return false;
            }

            $discount->boot($response);

            return true;
        });
    }

    /**
     * Return all discounts except given
     *
     * @param  array  $exceps
     * @return array
     */
    public function exceptDiscounts($exceps = [])
    {
        return $this->getDiscounts($exceps);
    }


    /**
     * Register all discounts into product
     *
     * @param  object  $item
     * @param  array  $discounts
     * @param  callable  $rule
     *
     * @return object
     */
    public function applyDiscounts($item, array $discounts = null, callable $canApplyDiscount)
    {
        $discounts = $discounts !== null ? $discounts : $this->getDiscounts();

        foreach ($discounts as $discount) {

            //If discount is allowed for basket items
            if ( $canApplyDiscount($discount, $item) ) {
                $item->addDiscount($discount);
            }
        }

        return $item;
    }

}

?>