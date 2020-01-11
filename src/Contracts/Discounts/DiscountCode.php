<?php

namespace AdminEshop\Contracts\Discounts;

use Admin;
use AdminEshop\Contracts\Discounts\Discount;

class DiscountCode extends Discount
{
    /*
     * Discount name
     */
    public $name = 'code';

    /*
     * Discount code key in session
     */
    private $discountKey = 'basket.discount';

    /*
     * Check if is discount active
     */
    public function isActive()
    {
        return $this->getDiscountCode() ?: false;
    }

    /**
     * Boot discount parameters after isActive check
     *
     * @param  mixed  $code
     * @return void
     */
    public function boot($code)
    {
        $this->operator = $code->discount_percent ? '-%' : '-';

        $this->value = $code->discount_percent ?: $code->discount_price;

        $this->canApplyOnProductInBasket = $code->discount_percent ? true : false;

        $this->freeDelivery = $code->free_delivery ? true : false;

        $this->code = $code;
    }

    /**
     * Check if discount code does exists
     *
     * @param  string|null  $code
     * @return bool
     */
    public function getDiscountCode($code = null)
    {
        //If code is not present, use code from session
        if ( $code === null ) {
            $code = session($this->discountKey);
        }

        //If any code is present
        if ( ! $code ) {
            return;
        }

        //Cache eloquent into class dataStore
        return $this->cache('code.'.$code, function() use ($code) {
            $model = Admin::getModelByTable('discounts_codes');

            return $model->where('code', $code)->whereRaw('`usage` > `used`')->first();
        });
    }

    /**
     * Save discount code into session
     *
     * @param  string  $code
     * @return this
     */
    public function saveDiscountCode(string $code)
    {
        session()->put($this->discountKey, $code);
        session()->save();

        return $this;
    }

    /**
     * Remove saved discount code
     *
     * @return  this
     */
    public function removeDiscountCode()
    {
        session()->forget($this->discountKey);
        session()->save();

        return $this;
    }
}

?>