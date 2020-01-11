<?php

namespace AdminEshop\Contracts\Discounts;

use Admin\Core\Contracts\DataStore;
use Discounts;
use Store;
use Cart;

class Discount
{
    use DataStore;

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
     * Can apply discount on products in cart
     *
     * @var  bool
     */
    public $canApplyOnProductInCart = false;

    /**
     * Can apply discount on products in whole website
     *
     * @var  bool
     */
    public $canApplyOnProduct = false;

    /**
     * Can apply free delivery on whole cart
     *
     * @var  bool
     */
    public $freeDelivery = false;

    /**
     * Discount message
     *
     * @var  string
     */
    public $message = '';

    /**
     * Discount key
     * Can be usefull if you need rewrite core discount
     *
     * @return string
     */
    public function getKey()
    {
        return class_basename(get_class($this));
    }

    /**
     * Returns discount name
     *
     * @return  string
     */
    public function getName()
    {
        return 'Your discount name';
    }

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
     * Return discount message
     *
     * @param  mixed  $isActiveResponse
     * @return void
     */
    public function getMessage($isActiveResponse)
    {
        if ( in_array($this->operator, ['+', '-', '*']) )
            return $this->value.' '.Store::getCurrency();

        if ( in_array($this->operator, ['+%', '-%']) )
            return $this->value.' %';
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
     * If discount can be applied in specific/all product in cart
     *
     * @param  Admin\Eloquent\AdminModel  $item
     * @return  bool
     */
    public function canApplyOnProductInCart($item)
    {
        return $this->canApplyOnProductInCart;
    }

    /**
     * Return all cart items without actual discount
     * If actual discount would be applied, intifity loop will throw and error
     *
     * @return  Collection
     */
    public function getCartItems()
    {
        $exceptAcutal = Discounts::getDiscounts([ $this->getKey() ]);

        return Cart::all($exceptAcutal);
    }

    /**
     * Check if discount is given from final price
     *
     * @return  bool
     */
    public function hasSummaryPriceOperator()
    {
        return in_array($this->operator, ['-', '+', '*']);
    }

    /**
     * Set discount message
     *
     * @param  mixed  $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Which field will be visible in the cart request
     *
     * @return  array
     */
    public function getVisible()
    {
        return [
            'key' => 'getKey',
            'name' => 'getName',
            'message',
            'operator',
            'value',
            'freeDelivery',
        ];
    }

    /**
     * Convert to array
     *
     * @return  array
     */
    public function toArray()
    {
        $data = [];

        foreach ($this->getVisible() as $key => $method) {
            if ( is_string($key) && is_string($method) ) {
                $value = method_exists($this, $method) ? $this->{$method}() : $this->{$key};
            } else {
                $key = $method;

                $value = $this->{$key};
            }

            $data[$key] = $value;
        }

        return $data;
    }
}

?>