<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Contracts\Discounts\Discount;
use AdminEshop\Eloquent\Concerns\DiscountSupport;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Store\PaymentsMethod;
use Discounts;
use Store;

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
     * We can rewrite vat value of model
     *
     * @var  int/float
     */
    protected $rewritedVatValue;

    /**
     * We can rewrite default price of model
     *
     * @var  int/float
     */
    protected $rewritedDefaultPrice;

    /**
     * Discounts array when model is going to array with toArray()
     *
     * @var  null
     */
    protected $toCartArrayDiscounts = null;

    /**
     * Log applied discounts on given model
     * for debug puposes
     *
     * @var  array
     */
    protected $appliedDiscounts = [];

    /**
     * All available price levels
     *
     * @var  array
     */
    protected $priceAttributes = [
        'initialPriceWithVat', 'initialPriceWithoutVat', //initial price without any discount
        'defaultPriceWithVat', 'defaultPriceWithoutVat', //initial price with product discount
        'priceWithVat', 'priceWithoutVat', 'clientPrice', //price with all prossible discounts
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
            if (
                $discounts === null
                || in_array($discount->getKey(), $allowedDiscounts)
            ) {
                $value = is_callable($callback = $discount->value) ? $this->runCallback($callback, $this, $price) : $discount->value;

                //If discount operator is set
                if ( $discount->operator && is_numeric($value) ) {
                    $originalPrice = $price;

                    $price = operator_modifier($price, $discount->operator, $value);

                    //Save all discounts applied on given model
                    $this->appliedDiscounts[$discount->getKey()] = [
                        'operator' => $discount->operator,
                        'operator_value' => $discount->value,
                        'old_price' => $originalPrice,
                        'new_price' => $price,
                    ];
                }
            }
        }

        return $price;
    }

    private function runCallback(callable $callback, DiscountSupport $item, $price)
    {
        return $callback($item, $price);
    }

    /*
     * Has product price with Vat?
     */
    public function showVatPrices()
    {
        return Store::hasB2B() ? false : true;
    }

    /*
     * Return pure default product price without all discounts and without TAX
     */
    public function getInitialPriceWithoutVatAttribute()
    {
        return Store::roundNumber($this->price);
    }

    /*
     * Return pure default product price without all discounts, with TAX
     */
    public function getInitialPriceWithVatAttribute($value)
    {
        return $this->calculateVatPrice($this->initialPriceWithoutVat);
    }

    /*
     * Price without TAX after initial product discounts
     */
    public function getDefaultPriceWithoutVatAttribute()
    {
        if ( $defaultPrice = $this->getRewritedDefaultPrice() ) {
            return Store::roundNumber($defaultPrice);
        }

        $price = operator_modifier($this->initialPriceWithoutVat, $this->discount_operator, $this->discount);

        return Store::roundNumber($price);
    }

    /*
     * Price without TAX after discounts
     */
    public function getDefaultPriceWithVatAttribute()
    {
        return $this->calculateVatPrice($this->defaultPriceWithoutVat);
    }

    /*
     * Returns price with discounts but without vat
     */
    public function getPriceWithoutVatAttribute()
    {
        $price = $this->applyDiscounts($this->defaultPriceWithoutVat, $this->toCartArrayDiscounts);

        return Store::roundNumber($price);
    }

    /*
     * Return price with vat & discounts
     */
    public function getPriceWithVatAttribute()
    {
        return $this->calculateVatPrice($this->priceWithoutVat);
    }

    /*
     * Return price with vat & discounts
     */
    public function totalPriceWithVat(int $quantity)
    {
        return Store::roundNumber(
            $this->calculateVatPrice($this->priceWithoutVat, null) * $quantity
        );
    }

    /**
     * Return B2B or B2C initial product price by client settings
     *
     * @return float
     */
    public function getInitialClientPriceAttribute()
    {
        if ( $this->showVatPrices() ) {
            return $this->initialPriceWithVat;
        }

        return $this->initialPriceWithoutVat;
    }

    /**
     * Return B2B or B2C with default product discount price by client settings
     *
     * @return float
     */
    public function getDefaultClientPriceAttribute()
    {
        if ( $this->showVatPrices() ) {
            return $this->defaultPriceWithVat;
        }

        return $this->defaultPriceWithoutVat;
    }

    /**
     * Return B2B or B2C price with all available discount by client settings
     *
     * @return float
     */
    public function getClientPriceAttribute()
    {
        if ( $this->showVatPrices() ) {
            return $this->priceWithVat;
        }

        return $this->priceWithoutVat;
    }

    /**
     * Calculation of vat price for given price
     *
     * @var $price int/float
     * @var $round bool/null
     *
     * @return  float/int
     */
    public function calculateVatPrice($price, $round = true)
    {
        //Set by configuration settings
        if ( $round === null ){
            $round = Store::hasSummaryRounding();
        }

        $vat = $this->vatValue;

        //If model has rewriten vat value
        if ( ! is_null($this->getRewritedVatValue()) ) {
            $vat = $this->getRewritedVatValue();
        }

        $price = $price * ($vat ? (1 + ($vat / 100)) : 1);

        return $round ? Store::roundNumber($price) : $price;
    }

    /**
     * Returns vat value attribute
     *
     * @return  int/float
     */
    public function getVatValueAttribute()
    {
        //If exists vat attribute
        if ( $this->vat_id === null && $this->vat !== null ) {
            return $this->vat;
        }

        return Store::getVatValueById($this->vat_id);
    }

    /**
     * Add all required price attributes for cart item array
     *
     * @var  array $discounts
     *
     * @return  array
     */
    public function toCartArray($discounts = null)
    {
        $this->append($this->getPriceAttributes());

        $this->toCartArrayDiscounts = $discounts;

        return $this->toArray();
    }

    /**
     * We can rewrite original model default price
     *
     * @param  int/float  $price
     * @return
     */
    public function rewriteDefaultPrice($price)
    {
        $this->rewritedDefaultPrice = $price;

        return $this;
    }

    /**
     * Rewrited default price getter
     *
     * @return  float/int
     */
    public function getRewritedDefaultPrice()
    {
        return $this->rewritedDefaultPrice;
    }

    /**
     * We can rewrite original model default price
     *
     * @param  int/float  $price
     * @return
     */
    public function rewriteVatValue($vatValue)
    {
        $this->rewritedVatValue = $vatValue;

        return $this;
    }

    /**
     * Rewrited default vat value
     *
     * @return  float/int
     */
    public function getRewritedVatValue()
    {
        return $this->rewritedVatValue;
    }

    /**
     * Return applied discounts on given property
     * with keys and applied discounts data
     *
     * @return  array
     */
    public function getAppliedDiscountsAttribute()
    {
        return $this->appliedDiscounts;
    }

    /**
     * Return applied discounts on given property
     * but only keys
     *
     * @return  array
     */
    public function getAppliedDiscountsKeysAttribute()
    {
        return array_keys($this->appliedDiscounts);
    }
}