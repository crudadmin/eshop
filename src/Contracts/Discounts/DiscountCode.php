<?php

namespace AdminEshop\Contracts\Discounts;

use Admin;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Discounts\Discount;
use AdminEshop\Contracts\Discounts\Discountable;
use AdminEshop\Models\Orders\Order;
use Store;
use Cart;

class DiscountCode extends Discount implements Discountable
{
    /*
     * Discount code key in session
     */
    private static $discountCodeKey = 'discount';

    /**
     * Discount code can't be applied outside cart
     *
     * @var  bool
     */
    public $canApplyOutsideCart = false;

    /*
     * Discount name
     */
    public function getName()
    {
        return __('Zľavový kód');
    }

    /**
     * Returns cache key for given discount
     *
     * We need set specific cache key, because if code will change in runtime,
     * we need reload this discount.
     *
     * @return  string
     */
    public function getCacheKey()
    {
        return $this->getKey().(self::getCodeName()?:'');
    }

    /*
     * Check if is discount active
     */
    public function isActive()
    {
        $code = self::getDiscountCode();

        return $code && !$this->getCodeError($code) ? $code : false;
    }

    /*
     * Check if is discount active in administration
     */
    public function isActiveInAdmin(Order $order)
    {
        //Get discount code in order, if exists..
        if ( $order->discount_code_id && $code = $order->discountCode ) {
            return $code && !$this->getCodeError($code) ? $code : false;
        }

        return false;
    }

    /**
     * Boot discount parameters after isActive check
     *
     * @param  mixed  $code
     * @return void
     */
    public function boot($code)
    {
        $this->operator = $code->discount_percentage ? '-%' : '-';

        $this->value = $code->discount_percentage ?: $code->discount_price;

        $this->freeDelivery = $code->free_delivery ? true : false;

        $this->code = $code;
    }

    /**
     * Returns validation error for given discount code
     *
     * @param  AdminEshop\Models\Store\DiscountsCode|null  $code
     *
     * @return  string
     */
    public function getCodeError($code = null)
    {
        if ( $code ) {
            //This rules cannot be applied in administration
            if ( Admin::isAdmin() === false ) {
                //Has been used order price
                if ( $code->isUsed ){
                    return _('Zadaný kód už bol použitý.');
                }

                //Expiration order price
                if ( $code->isExpired ){
                    return _('Zadaný kód expiroval.');
                }
            }

            //Minimum order price, can be applied also in administration
            $priceWithVat = @$this->getCartSummary()['priceWithVat'] ?: 0;
            if ( $code->min_order_price > 0 && $priceWithVat < $code->min_order_price ) {
                return sprintf(_('Minimálna suma objednávky pre tento kód je %s'), Store::priceFormat($code->min_order_price));
            }
        }

        else if ( !$code || $code->isActive == false ){
            return _('Zadaný kód nie je platný.');
        }

        return false;
    }

    /**
     * Apply this discount on models
     *
     * @return  array|null
     */
    public function applyOnModels()
    {
        //Allow apply on models only if is percentage discount from orders
        if ( $this->getResponse()->discount_percentage ) {
            return parent::applyOnModels();
        }
    }

    /**
     * If is fix price discount, then apply on whole order
     *
     * @return  bool
     */
    public function applyOnWholeCart()
    {
        $code = $this->getResponse();

        return $code->discount_price && !$code->discount_percentage ? true : false;
    }

    /**
     * Which field will be visible in the cart request
     *
     * @return  array
     */
    public function getVisible()
    {
        return array_merge(parent::getVisible(), ['code', 'freeDelivery']);
    }

    /**
     * Return discount message
     *
     * @param  mixed  $code
     * @return void
     */
    public function getMessage($code)
    {
        return $code->nameArray;
    }

    /**
     * When order is before creation status, you can modify order data
     * before creation from your discount.
     *
     * @param  array  $row
     * @return  array
     */
    public function mutateOrderRow(Order $order, CartCollection $items)
    {
        if ( $code = self::getDiscountCode() ) {
            $order->discount_code_id = $code->getKey();
        }
    }

    /**
     * Retreive discount code name from driver
     *
     * @return  string|null
     */
    public static function getCodeName()
    {
        return Cart::getDriver()->get(self::$discountCodeKey);
    }

    /**
     * Check if discount code does exists
     *
     * @param  string|null  $code
     * @return bool
     */
    public static function getDiscountCode($code = null)
    {
        //If code is not present, use code from session
        if ( $code === null ) {
            $code = self::getCodeName();
        }

        //If any code is present
        if ( ! $code ) {
            return;
        }

        //Cache eloquent into class dataStore
        return Admin::cache('code.'.$code, function() use ($code) {
            $model = Admin::getModelByTable('discounts_codes');

            return $model->where('code', $code)->first();
        });
    }

    /**
     * Save discount code into session
     *
     * @param  string  $code
     * @return this
     */
    public static function saveDiscountCode(string $code)
    {
        Cart::getDriver()->set(self::$discountCodeKey, $code);
    }

    /**
     * Remove saved discount code
     *
     * @return  this
     */
    public static function removeDiscountCode()
    {
        Cart::getDriver()->forget(self::$discountCodeKey);
    }
}

?>