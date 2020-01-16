<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\Discounts\Discount;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Store\PaymentsMethod;
use Discounts;
use Store;
use Admin;

trait PriceMutator
{
    /**
     * Here will be stored all additional products discount from cart
     *
     * @var  array
     */
    protected $registredDiscounts = [];

    /**
     * Can be discounts applied in administration?
     *
     * @var  bool
     */
    protected $applyDiscountsInAdmin = false;

    /**
     * All available price levels
     *
     * @var  array
     */
    protected $priceAttributes = [
        'initialPriceWithTax', 'initialPriceWithoutTax', //initial price without any discount
        'defaultPriceWithTax', 'defaultPriceWithoutTax', //initial price with product discount
        'priceWithTax', 'priceWithoutTax', 'clientPrice', //price with all prossible discounts
    ];

    /**
     * Add price attribute
     *
     * @param  string|array  $attribute
     */
    public function addPriceAttribute($attribute)
    {
        $this->priceAttributes = array_merge($this->priceAttributes, array_wrap($attribute));
    }

    /**
     * Returns registred price attributes
     *
     * @return  array
     */
    public function getPriceAttributes()
    {
        return $this->priceAttributes;
    }

    /**
     * Can be applied discounts on this model in administration?
     *
     * @return  bool
     */
    public function canApplyDiscountsInAdmin()
    {
        return $this->applyDiscountsInAdmin;
    }

    /**
     * Set apply discounts in admin
     *
     * @param  bool  $state
     */
    public function setApplyDiscountsInAdmin(bool $state)
    {
        $this->applyDiscountsInAdmin = $state;

        return $this;
    }

    /**
     * Add product discount
     *
     * @param  AdminEshop\Contracts\Discounts\Discount  $discount
     */
    public function addDiscount(Discount $discount)
    {
        $this->registredDiscounts[$discount->getKey()] = $discount;
    }

    /**
     * Apply given discounts on given price
     *
     * @param  float/int  $price
     * @param  array/null $discounts (null = all)
     * @return float/ing
     */
    public function applyDiscounts($price, $discounts = null)
    {
        //We skip all prices in administration for disabled models
        if ( $this->canApplyDiscountsInAdmin() === false && Admin::isAdmin() ) {
            return $price;
        }

        Discounts::applyDiscountsOnModel($this, $discounts, function($discount){
            return $discount->canApplyOutsideCart($this);
        });

        $allowedDiscounts = array_map(function($discount){
            return $discount->getKey();
        }, $discounts ?: []);

        //Apply all discounts into final price
        foreach ($this->registredDiscounts as $discount) {
            //Skip non allowed discounts
            if ( $discounts === null || in_array($discount->getKey(), $allowedDiscounts) ) {
                $value = is_callable($discount->value) ? $discount->value() : $discount->value;

                //If discount operator is set
                if ( $discount->operator && is_numeric($value) ) {
                    $price = operator_modifier($price, $discount->operator, $value);
                }
            }
        }

        return $price;
    }

    /*
     * Has product price with Tax?
     */
    public function showTaxPrices()
    {
        return Store::hasB2B() ? false : true;
    }

    /*
     * Return pure default product price without all discounts and without TAX
     */
    public function getInitialPriceWithoutTaxAttribute()
    {
        return Store::roundNumber($this->price);
    }

    /*
     * Return pure default product price without all discounts, with TAX
     */
    public function getInitialPriceWithTaxAttribute($value)
    {
        return Store::priceWithTax($this->initialPriceWithoutTax, $this->tax_id);
    }

    /*
     * Price without TAX after initial product discounts
     */
    public function getDefaultPriceWithoutTaxAttribute()
    {
        $price = operator_modifier($this->initialPriceWithoutTax, $this->discount_operator, $this->discount);

        return Store::roundNumber($price);
    }

    /*
     * Price without TAX after discounts
     */
    public function getDefaultPriceWithTaxAttribute()
    {
        return Store::priceWithTax($this->defaultPriceWithoutTax, $this->tax_id);
    }

    /*
     * Returns price with discounts but without tax
     */
    public function getPriceWithoutTaxAttribute()
    {
        $price = $this->applyDiscounts($this->defaultPriceWithoutTax);

        return Store::roundNumber($price);
    }

    /*
     * Return price with tax & discounts
     */
    public function getPriceWithTaxAttribute()
    {
        return Store::priceWithTax($this->priceWithoutTax, $this->tax_id);
    }

    /**
     * Return B2B or B2C initial product price by client settings
     *
     * @return float
     */
    public function getInitialClientPriceAttribute()
    {
        if ( $this->showTaxPrices() ) {
            return $this->initialPriceWithTax;
        }

        return $this->initialPriceWithoutTax;
    }

    /**
     * Return B2B or B2C with default product discount price by client settings
     *
     * @return float
     */
    public function getDefaultClientPriceAttribute()
    {
        if ( $this->showTaxPrices() ) {
            return $this->defaultPriceWithTax;
        }

        return $this->defaultPriceWithoutTax;
    }

    /**
     * Return B2B or B2C price with all available discount by client settings
     *
     * @return float
     */
    public function getClientPriceAttribute()
    {
        if ( $this->showTaxPrices() ) {
            return $this->priceWithTax;
        }

        return $this->priceWithoutTax;
    }

    /**
     * Returns tax value attribute
     *
     * @return  int/float
     */
    public function getTaxValueAttribute()
    {
        return Store::getTaxValueById($this->tax_id);
    }

    public function toCartArray()
    {
        $this->append($this->getPriceAttributes());

        return $this->toArray();
    }
}