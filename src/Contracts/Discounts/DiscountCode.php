<?php

namespace AdminEshop\Contracts\Discounts;

use Admin;

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
        return $this->getDiscountCode() ? true : false;
    }

    public function boot()
    {
        $code = $this->getDiscountCode();

        $this->operator = $code->discount_percent ? '-%' : '-';

        $this->value = $code->discount_percent ?: $code->dicount_price;

        $this->canApplyOnProductInBasket = $code->discount_percent ? true : false;

        $this->freeDelivery = $code->free_delivery ? true : false;
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

    /*
     * Save discount code into session
     */
    public function saveDiscountCode($code)
    {
        session()->put($this->discountKey, $code);
        session()->save();

        return $this;
    }
}

?>