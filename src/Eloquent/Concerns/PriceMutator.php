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
     * @param  array  $operators
     */
    public function addDiscount($key, $index, array $operators)
    {
        $this->registredDiscounts[$key] = [
            'index' => $index,
            'operators' => $operators,
        ];
    }

    /**
     * Returns applied discounts in correct order by eshop configuration
     *
     * @return  array
     */
    public function getRegistredDiscounts()
    {
        //We want sort discounts by correct order
        uasort($this->registredDiscounts, function($a, $b){
            return $a['index'] - $b['index'];
        });

        return $this->registredDiscounts;
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
        foreach ($this->getRegistredDiscounts() as $key => $registred) {
            //Skip non allowed discounts
            if ( $discounts === null || in_array($key, $allowedDiscounts) ) {
                foreach ($registred['operators'] as $operator) {
                    if ( $this->canOperatorBeAppliedOnModel($operator) === false ){
                        continue;
                    }

                    $value = is_callable($value = $operator['value'])
                                ? $this->runCallback($value, $this, $price)
                                : $value;

                    $operator = $operator['operator'];

                    //If discount operator is set
                    if ( $operator && is_numeric($value) ) {
                        $originalPrice = $price;

                        $price = operator_modifier($price, $operator, $value, $this->getRewritedVatValue());

                        //Save all discounts applied on given model
                        $this->appliedDiscounts[] = [
                            'discount' => $key,
                            'operator' => $operator,
                            'operator_value' => $value,
                            'old_price' => $originalPrice,
                            'new_price' => $price,
                        ];
                    }
                }
            }
        }

        return $price;
    }

    private function canOperatorBeAppliedOnModel($operator)
    {
        $classBasename = class_basename(get_class($this));

        $applyOnModels = $operator['applyOnModels'] ?? true;

        //Skip adding specific operator
        if (
            $applyOnModels === false
            || (
                is_array($applyOnModels)
                && in_array($classBasename, $applyOnModels) == false
            )
        ){
            return false;
        }

        return true;
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
        return $this->getAttribute('price');
    }

    /*
     * Return pure default product price without all discounts, with TAX
     */
    public function getInitialPriceWithVatAttribute($value)
    {
        return $this->calculateVatPrice($this->getAttribute('initialPriceWithoutVat'));
    }

    /*
     * Price without TAX after initial product discounts
     */
    public function getDefaultPriceWithoutVatAttribute()
    {
        if ( $defaultPrice = $this->getRewritedDefaultPrice() ) {
            return $defaultPrice;
        }

        $price = operator_modifier($this->getAttribute('initialPriceWithoutVat'), $this->getAttribute('discount_operator'), $this->getAttribute('discount'), $this->getRewritedVatValue());

        return config('admineshop.prices.round_without_vat', false) ? Store::roundNumber($price) : $price;
    }

    /*
     * Price without TAX after discounts
     */
    public function getDefaultPriceWithVatAttribute()
    {
        return $this->calculateVatPrice($this->getAttribute('defaultPriceWithoutVat'));
    }

    /*
     * Returns price with discounts but without vat
     */
    public function getPriceWithoutVatAttribute()
    {
        $price = $this->applyDiscounts($this->getAttribute('defaultPriceWithoutVat'), $this->toCartArrayDiscounts);

        return config('admineshop.prices.round_without_vat', false) ? Store::roundNumber($price) : $price;
    }

    /*
     * Return price with vat & discounts
     */
    public function getPriceWithVatAttribute()
    {
        return $this->calculateVatPrice($this->getAttribute('priceWithoutVat'));
    }

    /**
     * Return B2B or B2C initial product price by client settings
     *
     * @return float
     */
    public function getInitialClientPriceAttribute()
    {
        if ( $this->showVatPrices() ) {
            return $this->getAttribute('initialPriceWithVat');
        }

        return $this->getAttribute('initialPriceWithoutVat');
    }

    /**
     * Return B2B or B2C with default product discount price by client settings
     *
     * @return float
     */
    public function getDefaultClientPriceAttribute()
    {
        if ( $this->showVatPrices() ) {
            return $this->getAttribute('defaultPriceWithVat');
        }

        return $this->getAttribute('defaultPriceWithoutVat');
    }

    /**
     * Return B2B or B2C price with all available discount by client settings
     *
     * @return float
     */
    public function getClientPriceAttribute()
    {
        if ( $this->showVatPrices() ) {
            return $this->getAttribute('priceWithVat');
        }

        return $this->getAttribute('priceWithoutVat');
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

        $vat = $this->getRewritedVatValue();

        return Store::addVat($price, $vat, $round);
    }

    /**
     * Returns vat value attribute
     *
     * @return  int/float
     */
    public function getVatValueAttribute()
    {
        if ( $this->vat_id ) {
            return Store::getVatValueById($this->vat_id);
        }
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
        $rewritedVat = $this->rewritedVatValue;

        return is_null($rewritedVat) === false ? $rewritedVat : $this->getAttribute('vatValue');
    }

    /**
     * Return applied discounts on given property
     * with keys and applied discounts data
     * Available for debug purposes
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
        return array_filter(array_map(function($item){
            return $item['discount'] ?? null;
        }, $this->appliedDiscounts));
    }
}