<?php

namespace AdminEshop\Contracts\Discounts;

use Admin\Core\Contracts\DataStore;

class Discount
{
    use DataStore;

    /**
     * Discount name
     * Can be usefull if you need rewrite core discount
     *
     * @var  string
     */
    public $name = null;

    /**
     * Discount operator for managing price values
     * @see  +%, -%, +, -, *
     *
     * @var  null
     */
    public $operator = null;

    /**
     * Discount value
     *
     * @var  float/int
     */
    public $value = null;

    /**
     * Can apply discount on products in basket
     *
     * @var  bool
     */
    public $canApplyOnProductInBasket = false;

    /**
     * Can apply free delivery on whole basket
     *
     * @var  bool
     */
    public $freeDelivery = false;

    /**
     * Returns if discount is active
     *
     * @return  bool
     */
    public function isActive()
    {
        return false;
    }

    /**
     * Boot discount parameters after isActive check
     *
     * @return void
     */
    public function boot()
    {
        //$this->freeDelivery = true;
        //...
    }

    /**
     * If discount can be applied in specific/all producti in basket
     *
     * @param  object  $item
     * @return  bool
     */
    public function canApplyOnProductInBasket(object $item)
    {
        return $this->canApplyOnProductInBasket;
    }

    /*
     * Returns discount name
     */
    public function getDiscountName()
    {
        return $this->name ?: get_class($this);
    }
}

?>