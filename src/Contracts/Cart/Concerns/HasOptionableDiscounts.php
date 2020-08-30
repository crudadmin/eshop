<?php

namespace AdminEshop\Contracts\Cart\Concerns;

trait HasOptionableDiscounts
{
    /**
     * Apply discounts on given cart item
     *
     * @var  bool
     */
    public $discountableSupport = true;

    /**
     * Set discounts for given cart item
     *
     * @param  bool  $discountableSupport
     */
    public function setDiscounts(bool $discountableSupport = true)
    {
        $this->discountableSupport = $discountableSupport;

        return $this;
    }

    /**
     * Has cart item enabled discounts?
     *
     * @return bool
     */
    public function hasDiscounts()
    {
        return $this->discountableSupport;
    }
}