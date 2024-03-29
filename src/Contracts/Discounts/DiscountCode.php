<?php

namespace AdminEshop\Contracts\Discounts;

use Admin;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Discounts\Discount;
use AdminEshop\Contracts\Discounts\Discountable;
use AdminEshop\Models\Orders\Order;
use AdminEshop\Models\Store\DiscountsCode;
use Store;

class DiscountCode extends Discount implements Discountable
{
    /*
     * Discount code key in session
     */
    const DISCOUNT_CODE_KEY = 'discount';

    /**
     * Discount code can't be applied outside cart
     *
     * @var  bool
     */
    public $canApplyOutsideCart = false;

    /*
     * We does not want cache discount codes, because they may be changed in order
     */
    public $cachableResponse = false;

    /*
     * Discount code deliver ysetup
     */
    public $freeDelivery = false;

    /*
     * Discount name
     */
    public function getName()
    {
        return _('Zľavový kód');
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
        if ( Admin::isAdmin() ) {
            $identifier = ($order = $this->getOrder()) ? $order->discount_code_id : '-';
        } else {
            $identifier = implode(';', $this->getCodes());
        }

        return $this->getKey().($identifier?:'');
    }

    /*
     * Check if is discount active
     */
    public function isActive()
    {
        $codes = $this->getDiscountCodes()->reject(function($code){
            return $this->getCodeError($code) ? true : false;
        });

        if ( count($codes) == 0 ){
            return false;
        }

        return $codes;
    }

    /*
     * Check if is discount active in administration
     */
    public function isActiveInAdmin(Order $order)
    {
        //Get discount code in order, if exists..
        if ( $codes = $order->discount_codes ) {
            $codes = $codes->reject(function($code){
                return $this->getCodeError($code) ? true : false;
            });

            return $codes;
        }

        return false;
    }

    /**
     * Boot discount parameters after isActive check
     *
     * @param  mixed  $code
     * @return void
     */
    public function boot($codes)
    {
        //Apply multiple discounts
        foreach ($codes as $code) {
            $isPercentageCode = $code->discount_percentage && !$code->discount_price;
            $isPriceCode = !$code->discount_percentage && $code->discount_price;

            $this->operators[] = [
                'applyOnWholeCart' => $isPriceCode ? true : false,
                'applyOnModels' => $isPercentageCode ? true : false,
                'operator' => $code->discount_percentage ? '-%' : '-',
                'value' => $code->discount_percentage ?: $code->discount_price,
            ];
        }

        //Setup delivery at once from all cupouns
        $this->freeDelivery = $codes->filter(function($code){
            return $code->free_delivery;
        })->count() > 0;
    }

    /**
     * Returns validation error for given discount code
     *
     * @param  AdminEshop\Models\Store\DiscountsCode|null  $code
     *
     * @return  string
     */
    public function getCodeError(DiscountsCode $code = null)
    {
        if ( $code ) {
            //This rules cannot be applied in administration
            if ( Admin::isAdmin() === false ) {
                //Has been used order price
                if ( $code->isUsed ){
                    return _('Zadaný kód už bol použitý.');
                }

                //Code is not valid yet
                if ( $code->isBeforeValidDate ){
                    return sprintf(_('Zadaný kód platí od %s.'), $code->valid_from->format('d.m.Y'));
                }

                //Expiration order price
                if ( $code->isExpired ){
                    return sprintf(_('Zadaný kód expiroval %s.'), $code->valid_to->format('d.m.Y'));
                }
            }

            //Minimum order price, can be applied also in administration
            $priceWithVat = @$this->getCartSummary()['priceWithVat'] ?: 0;

            if ( $code->min_order_price > 0 && $priceWithVat < $code->min_order_price ) {
                return sprintf(_('Minimálna suma objednávky pre tento kód je %s'), Store::priceFormat($code->min_order_price));
            }

            if ( $code->discount_price > 0 && $priceWithVat < Store::calculateFromDefaultCurrency($code->discount_price) ) {
                return sprintf(_('Minimálna suma objednávky pre tento kód je %s'), Store::priceFormat($code->discount_price));
            }
        }

        else if ( !$code || $code->isActive == false ){
            return _('Zadaný kód nie je platný.');
        }

        return false;
    }

    /**
     * Which field will be visible in the cart request
     *
     * @return  array
     */
    public function getVisible()
    {
        return array_merge(parent::getVisible(), ['freeDelivery']);
    }

    /**
     * Return discount messages
     *
     * @param  mixed  $code
     *
     * @return array
     */
    public function getMessages($codes)
    {
        return $codes->map(function($code){
            return [
                'name' => $this->getName(),
                'code' => $code->setDiscountResponse(),
                'value' => $code->nameArray,
            ];
        })->toArray();
    }

    /**
     * When order is before creation status, you can modify order data
     * before creation from your discount.
     *
     * @param  array  $row
     * @return  array
     */
    public function mutateOrderRowAfter(Order $order, CartCollection $items)
    {
        if ( $codes = $this->getResponse() ) {
            $existingCodes = $order->discountCodes->pluck('id')->toArray();

            //If code does not exists in order yet.
            foreach ($codes as $code) {
                if ( in_array($code->getKey(), $existingCodes) == false ) {
                    $code->update([ 'used' => $code->used + 1 ]);
                }
            }

            $order->discountCodes()->sync(
                $codes->pluck('id')->toArray()
            );
        }
    }

    /**
     * Retreive discount code name from driver
     *
     * @return  string|null
     */
    public function getCodes()
    {
        return array_unique(array_filter(array_wrap(
            $this->getDriver()->get(self::DISCOUNT_CODE_KEY)
        )));
    }

    /**
     * Check if discount code does exists
     *
     * @param  string|array|null  $codes
     * @return bool
     */
    public function getDiscountCodes($codes = null)
    {
        //If code is not present, use code from session
        if ( $codes === null ) {
            $codes = $this->getCodes();
        }

        $codes = array_wrap($codes);

        //If any code is present
        if ( count($codes) == 0 ) {
            return collect();
        }

        //Cache eloquent into class dataStore
        return Admin::cache('code.'.implode(';', $codes), function() use ($codes) {
            $model = Admin::getModelByTable('discounts_codes');

            return $model->whereIn('code', $codes)->get();
        });
    }

    /**
     * Save discount code into session
     *
     * @param  string  $code
     *
     * @return this
     */
    public function setDiscountCode($code, $merge = true, $persist = true)
    {
        $codes = array_filter(array_wrap($code));

        if ( config('admineshop.discounts.codes.multiple', false) === true ) {
            $codes = $merge === true ? array_merge($this->getCodes(), $codes) : $codes;
        }

        $codes = array_filter(array_unique($codes));

        $this->getDriver()->set(self::DISCOUNT_CODE_KEY, $codes, $persist);

        return $this;
    }

    /**
     * Remove saved discount code
     *
     * @return  this
     */
    public function removeDiscountCode(string $code = null, $persist = true)
    {
        if ( config('admineshop.discounts.codes.multiple', false) === false ) {
            $this->getDriver()->forget(self::DISCOUNT_CODE_KEY);
        }

        //Remove only given code
        else if (!empty($code)) {
            $codes = $this->getCodes();

            if ( $index = array_search($code, $codes) ){
                unset($codes[$index]);
            }

            $this->getDriver()->set(self::DISCOUNT_CODE_KEY, $codes);
        }

        return $this;
    }
}

?>