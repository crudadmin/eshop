<?php

namespace AdminEshop\Contracts\Discounts;

use Admin;
use AdminEshop\Contracts\Discounts\Discount;
use Store;

class DiscountCode extends Discount
{
    /*
     * Discount code key in session
     */
    private $sessionKey = 'cart.discount';

    /*
     * Discount name
     */
    public function getName()
    {
        return __('Zľavový kód');
    }

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

        $this->canApplyOnProductInCart = $code->discount_percent ? true : false;

        $this->freeDelivery = $code->free_delivery ? true : false;

        $this->code = $code;
    }

    /**
     * Which field will be visible in the cart request
     *
     * @return  array
     */
    public function getVisible()
    {
        return array_merge(parent::getVisible(), ['code']);
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
            $code = session($this->sessionKey);
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
        session()->put($this->sessionKey, $code);
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
        session()->forget($this->sessionKey);
        session()->save();

        return $this;
    }

    /**
     * Return discount message
     *
     * @param  mixed  $code
     * @return void
     */
    public function getMessage($code)
    {
        $value = '';
        $freeDeliveryText = '';

        //If is only discount from order sum
        if ($code->discount_price) {
            $value = Store::priceFormat($code->discount_price);
            $valueWithTax = Store::priceFormat(Store::priceWithTax($code->discount_price));
        }

        //If is percentual discount
        else if ($code->percentage_price) {
            $value .= $code->percentage_price.'%';
        }

        //If has free delivery
        if ( $code->free_delivery ) {
            $freeDelivery = ($code->discount_price ?: $code->discount_price) > 0 ? ' + ' : '';
            $freeDelivery .= _('Doprava zdarma');
        }

        return [
            'withTax' => (@$valueWithTax ?: $value) . $freeDelivery,
            'withoutTax' => $value . $freeDelivery,
        ];
    }
}

?>