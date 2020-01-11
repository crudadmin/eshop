<?php

namespace AdminEshop\Contracts\Discounts;

use Admin\Core\Contracts\DataStore;
use StoreDiscounts;
use Basket;

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
     * Can apply discount on products in whole website
     *
     * @var  bool
     */
    public $canApplyOnProduct = false;

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
     * @param  mixed  $isActiveResponse
     * @return void
     */
    public function boot($isActiveResponse)
    {
        //$this->freeDelivery = true;
        //...
    }

    /**
     * If discount can be applied in specific/all product on whole website
     *
     * @param  Admin\Eloquent\AdminModel  $item
     * @return  bool
     */
    public function canApplyOnProduct($item)
    {
        return $this->canApplyOnProduct;
    }

    /**
     * If discount can be applied in specific/all product in basket
     *
     * @param  Admin\Eloquent\AdminModel  $item
     * @return  bool
     */
    public function canApplyOnProductInBasket($item)
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

    /**
     * Return all basket items without actual discount
     * If actual discount would be applied, intifity loop will throw and error
     *
     * @return  Collection
     */
    public function getBasketItems()
    {
        $exceptAcutal = StoreDiscounts::getDiscounts([ $this->getDiscountName() ]);

        return Basket::all($exceptAcutal);
    }
}

?>