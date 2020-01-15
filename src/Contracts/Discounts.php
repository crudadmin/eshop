<?php

namespace AdminEshop\Contracts;

use AdminEshop\Contracts\Discounts\DiscountCode;
use AdminEshop\Contracts\Discounts\FreeDelivery;
use Admin\Core\Contracts\DataStore;
use Store;

class Discounts
{
    use DataStore;

    /*
     * All registered discounts applied on cart items and whole store
     */
    private $discounts = [
        DiscountCode::class,
        FreeDelivery::class,
    ];

    /*
     * Which model attributes are discountable
     * 'myOtherParam' => true/false
     * (true = withTax / false = withoutTax / auto = 'by client type')
     */
    private $discountableAttributes = [
        'clientPrice' => 'auto',
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
     * Get all registered discounts in cart
     *
     * @param  array|string  $exceps
     * @return array
     */
    public function getDiscounts($exceps = [])
    {
        $exceps = array_wrap($exceps);

        $discounts = $this->cache('store_discounts', function(){
            return array_map(function($className){
                return new $className;
            }, $this->discounts);
        });

        //Returns only active discounts
        return array_values(array_filter($discounts, function($discount) use ($exceps) {
            if ( in_array($discount->getKey(), $exceps) || !($response = $discount->isActive()) ) {
                return false;
            }

            //Set is active response
            $discount->setResponse($response);
            $discount->boot($response);
            $discount->setMessage($discount->getMessage($response));

            return true;
        }));
    }

    /**
     * Return all discounts except given
     *
     * @param  array|string  $exceps
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
    public function applyDiscountsOnModel($item, array $discounts = null, callable $canApplyDiscount)
    {
        $discounts = $discounts !== null ? $discounts : $this->getDiscounts();

        foreach ($discounts as $discount) {

            //If discount is allowed for cart items
            if ( $discount->canApplyOnModel($item) && $canApplyDiscount($discount, $item) ) {
                $item->addDiscount($discount);
            }
        }

        return $item;
    }

    /**
     * Add discountable attribute
     *
     * @param  string|array  $key
     */
    public function addDiscountableAttributes($attributes)
    {
        $this->discountableAttributes = array_merge(
            $this->discountableAttributes,
            $attributes
        );

        return $this;
    }

    /**
     * Return discountable attributes
     *
     * @return  array
     */
    public function getDiscountableAttributes()
    {
        return $this->discountableAttributes;
    }

    /**
     * Returns value of tax for given parameter
     *
     * @return  bool|null
     */
    public function getDiscountableAttributeTaxValue($key)
    {
        $discountableAttributes = $this->getDiscountableAttributes();

        //If is not discountable attribute by withTax/WithouTax
        //try other dynamic fields from discounts settings
        if ( array_key_exists($key, $discountableAttributes) ) {
            $taxValue = $discountableAttributes[$key];

            return $taxValue == 'auto' ? !Store::hasB2B() : $taxValue;
        }
    }

}

?>