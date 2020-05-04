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
     * We can rewrite tax value of model
     *
     * @var  int/float
     */
    protected $rewritedTaxValue;

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
                $value = is_callable($callback = $discount->value) ? $callback($this) : $discount->value;

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
        return $this->calculateTaxPrice($this->initialPriceWithoutTax);
    }

    /*
     * Price without TAX after initial product discounts
     */
    public function getDefaultPriceWithoutTaxAttribute()
    {
        if ( $defaultPrice = $this->getRewritedDefaultPrice() ) {
            return Store::roundNumber($defaultPrice);
        }

        $price = operator_modifier($this->initialPriceWithoutTax, $this->discount_operator, $this->discount);

        return Store::roundNumber($price);
    }

    /*
     * Price without TAX after discounts
     */
    public function getDefaultPriceWithTaxAttribute()
    {
        return $this->calculateTaxPrice($this->defaultPriceWithoutTax);
    }

    /*
     * Returns price with discounts but without tax
     */
    public function getPriceWithoutTaxAttribute()
    {
        $price = $this->applyDiscounts($this->defaultPriceWithoutTax, $this->toCartArrayDiscounts);

        return Store::roundNumber($price);
    }

    /*
     * Return price with tax & discounts
     */
    public function getPriceWithTaxAttribute()
    {
        return $this->calculateTaxPrice($this->priceWithoutTax);
    }

    /*
     * Return price with tax & discounts
     */
    public function totalPriceWithTax(int $quantity)
    {
        $round = Store::hasSummaryRounding();

        return Store::roundNumber($this->calculateTaxPrice($this->priceWithoutTax, $round) * $quantity);
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
     * Calculation of tax price for given price
     *
     * @var $price int/float
     * @var $round bool
     *
     * @return  float/int
     */
    private function calculateTaxPrice($price, $round = true)
    {
        $tax = $this->taxValue;

        //If model has rewriten tax value
        if ( ! is_null($this->getRewritedTaxValue()) ) {
            $tax = $this->getRewritedTaxValue();
        }

        $price = $price * ($tax ? (1 + ($tax / 100)) : 1);

        return $round ? Store::roundNumber($price) : $price;
    }

    /**
     * Returns tax value attribute
     *
     * @return  int/float
     */
    public function getTaxValueAttribute()
    {
        //If exists tax attribute
        if ( $this->tax_id === null && $this->tax !== null ) {
            return $this->tax;
        }

        return Store::getTaxValueById($this->tax_id);
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
    public function rewriteTaxValue($taxValue)
    {
        $this->rewritedTaxValue = $taxValue;

        return $this;
    }

    /**
     * Rewrited default tax value
     *
     * @return  float/int
     */
    public function getRewritedTaxValue()
    {
        return $this->rewritedTaxValue;
    }
}